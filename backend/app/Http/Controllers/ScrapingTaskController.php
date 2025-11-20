<?php

namespace App\Http\Controllers;

use App\Models\ScrapingTask;
use App\Models\ScrapingResult;
use App\Jobs\CreateBookFromMetadataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class ScrapingTaskController extends Controller
{
    /**
     * 获取任务列表
     */
    public function index(Request $request)
    {
        $query = ScrapingTask::query()->where('user_id', $request->user()->id);

        // 筛选条件
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 排序
        $query->orderBy('created_at', 'desc');

        // 分页
        $tasks = $query->paginate($request->input('per_page', 15));

        return response()->json($tasks);
    }

    /**
     * 创建任务
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.provider' => 'required|string',
            'items.*.source_id' => 'required|string',
            'items.*.source_url' => 'required|url',
            'items.*.query' => 'required|string',
            'items.*.metadata' => 'required|array',
            'options' => 'array',
            'options.auto_download_cover' => 'boolean',
            'options.skip_existing' => 'boolean',
        ]);

        // 创建任务记录
        $task = DB::transaction(function () use ($validated, $request) {
            $task = ScrapingTask::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
                'status' => 'pending',
                'total_items' => count($validated['items']),
                'processed_items' => 0,
                'success_items' => 0,
                'failed_items' => 0,
                'options' => $validated['options'] ?? [],
            ]);

            // 创建详细结果记录
            foreach ($validated['items'] as $item) {
                $task->results()->create([
                    'provider' => $item['provider'],
                    'source_id' => $item['source_id'],
                    'source_url' => $item['source_url'],
                    'query' => $item['query'],
                    'metadata' => $item['metadata'],
                    'status' => 'pending',
                ]);
            }

            return $task;
        });

        // 分发批量任务
        $this->dispatchJobs($task);

        return response()->json($task->load('results'), 201);
    }

    /**
     * 获取任务详情
     */
    public function show(Request $request, int $id)
    {
        $task = ScrapingTask::where('user_id', $request->user()->id)
            ->with(['results' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->findOrFail($id);

        return response()->json($task);
    }

    /**
     * 取消任务
     */
    public function cancel(Request $request, int $id)
    {
        $task = ScrapingTask::where('user_id', $request->user()->id)->findOrFail($id);

        if (!in_array($task->status, ['pending', 'processing'])) {
            return response()->json(['error' => '任务已完成或已取消，无法取消'], 422);
        }

        $task->update([
            'status' => 'cancelled',
            'finished_at' => now(),
        ]);

        // 取消所有待处理的结果
        $task->results()->where('status', 'pending')->update([
            'status' => 'skipped',
            'error_message' => '任务已取消',
        ]);

        return response()->json($task);
    }

    /**
     * 删除任务
     */
    public function destroy(Request $request, int $id)
    {
        $task = ScrapingTask::where('user_id', $request->user()->id)->findOrFail($id);
        $task->delete();

        return response()->json(['message' => '任务已删除']);
    }

    /**
     * 获取任务结果列表
     */
    public function results(Request $request, int $id)
    {
        $task = ScrapingTask::where('user_id', $request->user()->id)->findOrFail($id);

        $query = $task->results();

        // 筛选条件
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 排序
        $query->orderBy('created_at', 'asc');

        // 分页
        $results = $query->paginate($request->input('per_page', 20));

        return response()->json($results);
    }

    /**
     * 分发队列任务
     */
    protected function dispatchJobs(ScrapingTask $task): void
    {
        $task->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $options = $task->options ?? [];

        // 为每个结果创建一个Job
        $jobs = [];
        foreach ($task->results as $result) {
            $jobs[] = new CreateBookFromMetadataJob($result, $options);
        }

        // 使用批量任务
        $batch = Bus::batch($jobs)
            ->name("scraping_task_{$task->id}")
            ->then(function () use ($task) {
                // 所有任务完成
                $task->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                ]);
                $task->updateProgress();
            })
            ->catch(function () use ($task) {
                // 有任务失败
                $task->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error_message' => '部分任务执行失败',
                ]);
                $task->updateProgress();
            })
            ->dispatch();
    }
}
