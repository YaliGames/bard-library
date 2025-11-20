<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Author;
use App\Models\Tag;
use App\Models\Series;
use App\Models\File;
use App\Services\Metadata\MetadataConfigLoader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 图书创建和元数据处理服务
 * 用于统一处理从元数据创建图书的逻辑
 */
class BookCreationService
{
    /**
     * 从元数据创建图书
     * 
     * @param array $metadata 元数据数组
     * @param int|null $userId 创建用户ID
     * @param array $options 选项配置
     * @return Book 创建的图书实例
     */
    public function createFromMetadata(array $metadata, ?int $userId = null, array $options = []): Book
    {
        // 规范化出版日期
        $publishedAt = $this->normalizePublishedDate(
            $metadata['published_at'] ?? null
        );
        
        // 创建图书基本信息
        $book = Book::create([
            'title' => $metadata['title'] ?? 'Untitled',
            'subtitle' => $metadata['subtitle'] ?? null,
            'description' => $metadata['description'] ?? null,
            'rating' => $metadata['rating'] ?? null,
            'publisher' => $metadata['publisher'] ?? null,
            'published_at' => $publishedAt,
            'isbn13' => $metadata['isbn13'] ?? $metadata['isbn'] ?? null,
            'isbn10' => $metadata['isbn10'] ?? null,
            'language' => $metadata['language'] ?? null,
            'meta' => [
                'source' => $metadata['source'] ?? null,
            ],
            'created_by' => $userId,
        ]);
        
        // 关联作者
        if (!empty($metadata['authors'])) {
            $this->syncAuthors($book, $metadata['authors']);
        }
        
        // 关联标签
        if (!empty($metadata['tags'])) {
            $this->syncTags($book, $metadata['tags']);
        }
        
        // 关联丛书
        if (!empty($metadata['series'])) {
            $this->syncSeries($book, $metadata['series'], $metadata['series_index'] ?? null);
        }
        
        // 下载并保存封面
        if (!empty($metadata['cover']) && ($options['auto_download_cover'] ?? false)) {
            $provider = $metadata['source']['id'] ?? null;
            $this->downloadAndAttachCover($book, $metadata['cover'], $provider);
        }
        
        return $book->fresh(['authors', 'tags', 'series']);
    }
    
    /**
     * 规范化出版日期格式
     * 
     * @param string|null $date 原始日期字符串
     * @return string|null 规范化后的日期（YYYY-MM-DD）
     */
    public function normalizePublishedDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }
        
        // 如果只有年份（4位数字），补充为完整日期
        if (preg_match('/^\d{4}$/', $date)) {
            return $date . '-01-01';
        }
        
        // 如果是 YYYY-MM 格式，补充日
        if (preg_match('/^\d{4}-\d{2}$/', $date)) {
            return $date . '-01';
        }
        
        // 如果已经是完整格式，直接返回
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        
        // 尝试从字符串中提取日期
        if (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        
        if (preg_match('/(\d{4})-(\d{1,2})/', $date, $m)) {
            return sprintf('%04d-%02d-01', $m[1], $m[2]);
        }
        
        if (preg_match('/(\d{4})/', $date, $m)) {
            return $m[1] . '-01-01';
        }
        
        return null;
    }
    
    /**
     * 同步作者关系
     * 支持传入作者ID数组或作者名称数组，或混合数组
     * 
     * @param Book $book 图书实例
     * @param array $authors 作者数组（可以是ID或名称）
     */
    public function syncAuthors(Book $book, array $authors): void
    {
        if (empty($authors)) {
            return;
        }
        
        $authorIds = [];
        
        foreach ($authors as $author) {
            if (empty($author)) {
                continue;
            }
            
            $author = is_string($author) ? trim($author) : $author;
            
            if (is_numeric($author)) {
                // 数字直接作为ID
                $authorIds[] = (int)$author;
            } else {
                // 字符串作为名称，先查找是否存在
                $existingAuthor = Author::where('name', $author)->first();
                
                if ($existingAuthor) {
                    $authorIds[] = $existingAuthor->id;
                } else {
                    // 不存在则创建
                    $newAuthor = Author::create([
                        'name' => $author,
                        'sort_name' => $author,
                    ]);
                    $authorIds[] = $newAuthor->id;
                }
            }
        }
        
        if (!empty($authorIds)) {
            $book->authors()->sync(array_unique($authorIds));
        }
    }
    
    /**
     * 同步标签关系
     * 
     * @param Book $book 图书实例
     * @param array $tags 标签数组（可以是ID或名称）
     */
    public function syncTags(Book $book, array $tags): void
    {
        if (empty($tags)) {
            return;
        }
        
        $tagIds = [];
        
        foreach ($tags as $tag) {
            if (empty($tag)) {
                continue;
            }
            
            $tag = is_string($tag) ? trim($tag) : $tag;
            
            if (is_numeric($tag)) {
                $tagIds[] = (int)$tag;
            } else {
                $existingTag = Tag::where('name', $tag)->first();
                
                if ($existingTag) {
                    $tagIds[] = $existingTag->id;
                } else {
                    $newTag = Tag::create([
                        'name' => $tag,
                        'type' => null,
                    ]);
                    $tagIds[] = $newTag->id;
                }
            }
        }
        
        if (!empty($tagIds)) {
            $book->tags()->sync(array_unique($tagIds));
        }
    }
    
    /**
     * 同步丛书关系
     * 
     * @param Book $book 图书实例
     * @param int|string $series 丛书ID或名称
     * @param int|null $index 丛书编号
     */
    public function syncSeries(Book $book, $series, ?int $index = null): void
    {
        if (empty($series)) {
            return;
        }
        
        $seriesId = null;
        
        if (is_numeric($series)) {
            $seriesId = (int)$series;
        } else {
            $seriesName = trim((string)$series);
            if ($seriesName !== '') {
                $seriesModel = Series::firstOrCreate(['name' => $seriesName]);
                $seriesId = $seriesModel->id;
            }
        }
        
        if ($seriesId) {
            $book->update([
                'series_id' => $seriesId,
                'series_index' => $index,
            ]);
        }
    }
    
    /**
     * 下载封面并关联到图书
     * 参考 CoversController::fromUrl 的实现
     * 使用 SHA256 去重，避免重复存储相同封面
     * 
     * @param Book $book 图书实例
     * @param string $coverUrl 封面URL
     * @param string|null $provider 元数据提供商ID（用于获取Referer等headers）
     * @return bool 是否成功
     */
    public function downloadAndAttachCover(Book $book, string $coverUrl, ?string $provider = null): bool
    {
        try {
            // 准备请求头
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ];
            
            // 根据 provider 或 URL 设置 Referer
            if ($provider) {
                $loader = new MetadataConfigLoader();
                $config = $loader->load($provider);
                if ($config && !empty($config['base_url'])) {
                    $headers['Referer'] = $config['base_url'];
                }
            } elseif (str_contains($coverUrl, 'doubanio.com') || str_contains($coverUrl, 'book.douban.com')) {
                $headers['Referer'] = 'https://book.douban.com/';
            }
            
            // 下载图片
            $response = Http::withHeaders($headers)->timeout(30)->get($coverUrl);
            
            if (!$response->successful()) {
                Log::warning("[BookCreationService] 封面下载失败", [
                    'book_id' => $book->id,
                    'cover_url' => $coverUrl,
                    'status' => $response->status(),
                ]);
                return false;
            }
            
            $body = $response->body();
            if (!$body) {
                Log::warning("[BookCreationService] 封面内容为空", [
                    'book_id' => $book->id,
                    'cover_url' => $coverUrl,
                ]);
                return false;
            }
            
            // 计算 SHA256（用于去重）
            $sha256 = hash('sha256', $body);
            
            // 检查是否已存在相同的封面文件（避免重复存储）
            $existingFile = File::where('sha256', $sha256)
                ->where('format', 'cover')
                ->first();
            
            if ($existingFile) {
                // 复用已存在的文件
                $book->update(['cover_file_id' => $existingFile->id]);
                
                Log::info("[BookCreationService] 复用已存在的封面", [
                    'book_id' => $book->id,
                    'file_id' => $existingFile->id,
                    'sha256' => $sha256,
                ]);
                
                return true;
            }
            
            // 确定文件扩展名
            $contentType = strtolower($response->header('Content-Type', 'image/jpeg'));
            $ext = 'jpg';
            if (str_contains($contentType, 'png') || str_contains(strtolower($coverUrl), '.png')) {
                $ext = 'png';
            } elseif (str_contains($contentType, 'webp') || str_contains(strtolower($coverUrl), '.webp')) {
                $ext = 'webp';
            }
            
            // 生成路径（使用 SHA256 分层存储，参考 CoversController）
            $a = substr($sha256, 0, 2);
            $b = substr($sha256, 2, 2);
            $filename = "{$sha256}.{$ext}";
            $relativePath = "covers/{$a}/{$b}/{$sha256}/{$filename}";
            
            // 保存到 library 存储
            $disk = Storage::disk('library');
            if (!$disk->exists($relativePath)) {
                $disk->put($relativePath, $body);
            }
            
            // 创建 File 记录
            $file = File::create([
                'book_id' => $book->id,
                'format' => 'cover',
                'size' => strlen($body),
                'mime' => match ($ext) {
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    default => 'image/jpeg',
                },
                'sha256' => $sha256,
                'path' => $relativePath,
                'storage' => 'library',
                'pages' => null,
            ]);
            
            // 更新图书封面关联
            $book->update(['cover_file_id' => $file->id]);
            
            Log::info("[BookCreationService] 封面下载成功", [
                'book_id' => $book->id,
                'file_id' => $file->id,
                'path' => $relativePath,
                'sha256' => $sha256,
                'size_kb' => round(strlen($body) / 1024, 2),
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("[BookCreationService] 封面下载异常", [
                'book_id' => $book->id,
                'cover_url' => $coverUrl,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * 检查图书是否已存在（按ISBN）
     * 
     * @param array $metadata 元数据数组
     * @return Book|null 已存在的图书或null
     */
    public function findExistingByIsbn(array $metadata): ?Book
    {
        $isbn13 = $metadata['isbn13'] ?? $metadata['isbn'] ?? null;
        $isbn10 = $metadata['isbn10'] ?? null;
        
        if (!$isbn13 && !$isbn10) {
            return null;
        }
        
        return Book::where(function ($q) use ($isbn13, $isbn10) {
            if ($isbn13) {
                $q->orWhere('isbn13', $isbn13);
            }
            if ($isbn10) {
                $q->orWhere('isbn10', $isbn10);
            }
        })->first();
    }
}

