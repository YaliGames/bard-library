<?php

namespace App\Jobs;

use App\Models\ScrapingResult;
use App\Models\Book;
use App\Services\BookCreationService;
use App\Services\Metadata\MetadataConfigLoader;
use App\Services\Metadata\MetadataScraper;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateBookFromMetadataJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ScrapingResult $result,
        public array $options = []
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(BookCreationService $bookService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        try {
            // 获取完整的详情页数据
            $metadata = $this->fetchFullMetadata();
            
            // 按 ISBN检查是否已存在
            if ($this->options['skip_existing'] ?? true) {
                $existing = $bookService->findExistingByIsbn($metadata);

                if ($existing) {
                    $this->result->update([
                        'status' => 'skipped',
                        'book_id' => $existing->id,
                        'error_message' => '图书已存在（ISBN重复）',
                        'processed_at' => now(),
                    ]);
                    $this->result->task->updateProgress();
                    return;
                }
            }

            // 3. 使用 BookCreationService 创建图书
            DB::transaction(function () use ($metadata, $bookService) {
                $book = $bookService->createFromMetadata(
                    $metadata,
                    $this->result->task->user_id,
                    $this->options
                );

                $this->result->update([
                    'status' => 'success',
                    'book_id' => $book->id,
                    'processed_at' => now(),
                ]);
            });

            $this->result->task->updateProgress();

        } catch (\Exception $e) {
            Log::error("[CreateBookFromMetadataJob] 创建图书失败", [
                'result_id' => $this->result->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->result->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            $this->result->task->updateProgress();
            throw $e;
        }
    }

    /**
     * 获取完整的元数据（从详情页）
     */
    protected function fetchFullMetadata(): array
    {
        $previewData = $this->result->metadata;
        $sourceUrl = $this->result->source_url;
        
        // 如果预览数据中已有完整字段（description, isbn等），则直接使用
        if (!empty($previewData['description']) || !empty($previewData['isbn'])) {
            Log::info("[CreateBookFromMetadataJob] 使用预览数据中的完整字段", [
                'has_description' => !empty($previewData['description']),
                'has_isbn' => !empty($previewData['isbn'])
            ]);
            return $previewData;
        }
        
        // 否则，直接使用 MetadataScraper 获取完整数据
        try {
            $provider = $this->result->provider;
            
            // 加载配置
            $loader = new \App\Services\Metadata\MetadataConfigLoader();
            $config = $loader->load($provider);
            
            if (!$config) {
                Log::warning("[CreateBookFromMetadataJob] 提供商配置未找到: {$provider}");
                return $previewData;
            }
            
            // 直接调用 scraper
            $scraper = new \App\Services\Metadata\MetadataScraper($config);
            $fullData = $scraper->fetchDetail($sourceUrl);
            
            Log::info("[CreateBookFromMetadataJob] 成功获取完整元数据", [
                'url' => $sourceUrl,
                'has_description' => !empty($fullData['description']),
                'has_isbn' => !empty($fullData['isbn']),
                'has_tags' => !empty($fullData['tags'])
            ]);
            
            // 合并预览数据和详情数据（详情数据优先）
            return array_merge($previewData, $fullData);
        } catch (\Exception $e) {
            Log::warning("[CreateBookFromMetadataJob] 获取详情页异常，使用预览数据", [
                'url' => $sourceUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $previewData;
        }
    }
}
