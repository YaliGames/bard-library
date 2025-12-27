<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Bookmark;
use App\Models\TxtChapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\ApiHelpers;

class TxtController extends Controller
{
    // 预览/获取章节列表（支持自定义正则 & dry 预览）
    public function chapters(Request $request, int $fileId)
    {
        $file = File::findOrFail($fileId);
        if (strtolower($file->format) !== 'txt') {
            return ApiHelpers::error('Not a TXT file', 422);
        }
        $pattern = $request->query('pattern');
        $dry = $request->boolean('dry');

        if (!$pattern && !$dry) {
            // 无自定义参数：优先返回已存在的章节，否则使用默认规则解析并入库
            $exists = TxtChapter::where('file_id',$fileId)->orderBy('index')->get();
            if ($exists->count() > 0) {
                // 统一结构并附带 book_id 和 book 信息
                $chapters = $exists->map(function($c) use ($file) {
                    return [
                        'book_id' => $file->book_id,
                        'file_id' => $c->file_id,
                        'index'   => $c->index,
                        'title'   => $c->title,
                        'offset'  => (int)$c->offset,
                        'length'  => (int)$c->length,
                    ];
                });
                
                // 返回包含 book 信息的完整响应
                return ApiHelpers::success([
                    'book' => $file->book ? [
                        'id' => $file->book->id,
                        'title' => $file->book->title,
                        'author' => $file->book->author,
                        'cover' => $file->book->cover,
                    ] : null,
                    'chapters' => $chapters,
                ], '', 200);
            }
            return $this->parse($fileId, null, true);
        }
        // 有 pattern 或 dry：只预览，不入库
        return $this->parse($fileId, $pattern ?: null, false);
    }

    // 提交保存（pattern 或显式的 chapters 列表）
    public function commit(Request $request, int $fileId)
    {
        $data = $request->validate([
            'pattern' => ['nullable','string'],
            'replace' => ['nullable','boolean'],
            'chapters' => ['nullable','array'],
            'chapters.*.index' => ['required_with:chapters','integer','min:0'],
            'chapters.*.title' => ['nullable','string','max:255'],
            'chapters.*.offset' => ['required_with:chapters','integer','min:0'],
            'chapters.*.length' => ['required_with:chapters','integer','min:0'],
        ]);
        $replace = $request->boolean('replace', true);
        if (!empty($data['pattern'])) {
            $list = $this->parse($fileId, $data['pattern'], true, $replace);
            return ApiHelpers::success(['saved' => is_countable($list) ? count($list) : null], '', 200);
        }
        if (!empty($data['chapters'])) {
            $file = File::findOrFail($fileId);
            if (strtolower($file->format) !== 'txt') {
                return ApiHelpers::error('Not a TXT file', 422);
            }
            DB::transaction(function() use ($fileId, $replace, $data) {
                if ($replace) TxtChapter::where('file_id',$fileId)->delete();
                foreach ($data['chapters'] as $i => $c) {
                    TxtChapter::create([
                        'file_id' => $fileId,
                        'index' => $c['index'] ?? $i,
                        'title' => $c['title'] ?? null,
                        'offset' => $c['offset'],
                        'length' => $c['length'],
                    ]);
                }
            });
            return ApiHelpers::success(['saved' => count($data['chapters'])], '', 200);
        }
        return ApiHelpers::error('pattern or chapters required', 422);
    }

    // 解析：可选自定义正则；$save=true 时覆盖写入数据库
    public function parse(int $fileId, ?string $pattern = null, bool $save = false, bool $replace = true)
    {
        $file = File::findOrFail($fileId);
        if (strtolower($file->format) !== 'txt') {
            return ApiHelpers::error('Not a TXT file', 422);
        }
        $disk = Storage::disk($file->storage ?: config('filesystems.default'));
        if (!$disk->exists($file->path)) return ApiHelpers::error('File missing', 404);
    // 读取原始字节并转换为 UTF-8，用该字符串进行正则与偏移计算
    $content = $this->ensureUtf8($disk->get($file->path));
    // 默认规则兼容 CRLF、中文与英文章节标题
    $pattern = $this->normalizePattern($pattern) ?: '/(^|\r?\n)(第\s*[一二三四五六七八九十百千0-9]+\s*[章节回部篇卷].*?$)|(^|\r?\n)(Chapter\s+\d+.*?$)/miu';
        // 校验正则
        set_error_handler(function(){}); // 抑制无害的 warning
        $ok = @preg_match($pattern, "");
        restore_error_handler();
        if ($ok === false) {
            return ApiHelpers::error('Invalid regex pattern', 422);
        }
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
        $positions = [];
        foreach ($matches[0] as $m) { $positions[] = $m[1]; }
        array_unshift($positions, 0);
        $positions = array_values(array_unique(array_filter($positions, fn($p)=>$p!==null && $p>=0)));
        sort($positions);
        $chapters = [];
        for ($i=0; $i<count($positions); $i++) {
            $start = $this->adjustToTitleStart($content, $positions[$i]);
            // 将结束位置对齐到“下一章标题行”的真实起点，避免包含下一章的任何字符
            $rawEnd = ($i+1 < count($positions)) ? $positions[$i+1] : strlen($content);
            $end = $this->adjustToTitleStart($content, $rawEnd);
            if ($end < $start) { $end = $start; }
            $len = max(0, $end - $start);
            $title = $this->extractLineTitle($content, $start);
            $chapters[] = [
                'book_id' => $file->book_id,
                'file_id' => $fileId,
                'index' => count($chapters),
                'title' => $title,
                'offset' => $start,
                'length' => $len,
            ];
        }
        if ($save) {
            DB::transaction(function() use ($fileId, $replace, $chapters) {
                if ($replace) TxtChapter::where('file_id',$fileId)->delete();
                foreach ($chapters as $c) { TxtChapter::create($c); }
            });
        }
        
        // 返回包含 book 信息的完整响应
        return ApiHelpers::success([
            'book' => $file->book ? [
                'id' => $file->book->id,
                'title' => $file->book->title,
                'author' => $file->book->author,
                'cover' => $file->book->cover,
            ] : null,
            'chapters' => $chapters,
        ], '', 200);
    }

    // 获取某章节内容
    public function chapterContent(int $fileId, int $index)
    {
        $chapter = TxtChapter::where('file_id',$fileId)->where('index',$index)->firstOrFail();
        $file = File::findOrFail($fileId);
        $disk = Storage::disk($file->storage ?: config('filesystems.default'));
        if (!$disk->exists($file->path)) return ApiHelpers::error('File missing', 404);
        // 读取完整文件并与解析阶段一致地转换为 UTF-8，再基于偏移/长度截取
        $utf8 = $this->ensureUtf8($disk->get($file->path));
        $start = (int)$chapter->offset; $len = (int)$chapter->length;
        if ($start < 0) $start = 0; if ($len < 0) $len = 0;
        if ($start > strlen($utf8)) $start = strlen($utf8);
        $slice = substr($utf8, $start, $len);
        return ApiHelpers::success([
            'book_id' => $file->book_id,
            'file_id' => $fileId,
            'index' => $index,
            'title' => $chapter->title,
            'content' => $slice,
        ], '', 200);
    }

    // 获取整本书的所有章节内容
    public function fullContent(int $fileId)
    {
        $file = File::with('book')->findOrFail($fileId);
        if (strtolower($file->format) !== 'txt') {
            return ApiHelpers::error('Not a TXT file', 422);
        }

        // 获取所有章节
        $chapters = TxtChapter::where('file_id', $fileId)->orderBy('index')->get();
        if ($chapters->isEmpty()) {
            return ApiHelpers::error('No chapters found', 404);
        }

        // 读取完整文件内容
        $disk = Storage::disk($file->storage ?: config('filesystems.default'));
        if (!$disk->exists($file->path)) {
            return ApiHelpers::error('File missing', 404);
        }
        $utf8 = $this->ensureUtf8($disk->get($file->path));

        // 构建章节列表和内容
        $chaptersData = [];
        $contents = [];

        foreach ($chapters as $chapter) {
            $start = (int)$chapter->offset;
            $len = (int)$chapter->length;
            if ($start < 0) $start = 0;
            if ($len < 0) $len = 0;
            if ($start > strlen($utf8)) $start = strlen($utf8);
            
            $slice = substr($utf8, $start, $len);
            
            $chaptersData[] = [
                'index' => $chapter->index,
                'title' => $chapter->title,
                'offset' => $start,
                'length' => $len,
            ];
            
            $contents[$chapter->index] = $slice;
        }

        // 获取书籍标题和文件名
        $bookTitle = $file->book?->title;
        $fileName = basename($file->path);

        return ApiHelpers::success([
            'book_id' => $file->book_id,
            'file_id' => $fileId,
            'book_title' => $bookTitle,
            'file_name' => $fileName,
            'chapters' => $chaptersData,
            'contents' => $contents,
        ], '', 200);
    }

    // 重命名章节标题
    public function renameChapter(Request $request, int $fileId, int $index)
    {
        $data = $request->validate([
            'title' => ['nullable','string','max:255'],
        ]);
        $chapter = TxtChapter::where('file_id',$fileId)->where('index',$index)->firstOrFail();
        $chapter->title = $data['title'] ?? null;
        $chapter->save();
        return ApiHelpers::success([
            'index' => $chapter->index,
            'title' => $chapter->title,
            'offset' => (int)$chapter->offset,
            'length' => (int)$chapter->length,
            'file_id' => $chapter->file_id,
        ], '', 200);
    }

    // 删除并与相邻章节合并
    public function deleteWithMerge(Request $request, int $fileId, int $index)
    {
        $merge = $request->query('merge') ?: $request->input('merge');
        if (!in_array($merge, ['prev','next'], true)) {
            return ApiHelpers::error('merge must be prev or next', 422);
        }
        $file = File::findOrFail($fileId);
        $target = TxtChapter::where('file_id',$fileId)->where('index',$index)->firstOrFail();
        $adjIndex = $merge === 'prev' ? $index - 1 : $index + 1;
        $adjacent = TxtChapter::where('file_id',$fileId)->where('index',$adjIndex)->first();
        if (!$adjacent) {
            return ApiHelpers::error('Adjacent chapter not found', 422);
        }

        DB::transaction(function() use ($merge, $target, $adjacent, $fileId, $index, $file) {
            if ($merge === 'prev') {
                // 合并到上一个：上一个的长度扩展覆盖目标的末尾
                $adjacent->length = max(0, ($target->offset + $target->length) - $adjacent->offset);
                $adjacent->save();
                $target->delete();
            } else {
                // 合并到下一个：把下一个的起点移到当前起点，并重算长度
                $end = $adjacent->offset + $adjacent->length;
                $adjacent->offset = min($target->offset, $adjacent->offset);
                $adjacent->length = max(0, $end - $adjacent->offset);
                $adjacent->save();
                $target->delete();
            }
            // 重新规范 index（从 0 开始递增）
            $all = TxtChapter::where('file_id',$fileId)->orderBy('offset')->get();
            $i = 0;
            foreach ($all as $c) { $c->index = $i++; $c->save(); }

            // TO BE REMOVED: 修正用户书签（仅限指向该 TXT 文件的书签）
            if (false) {
                $bms = Bookmark::where('book_id', $file->book_id)
                    ->where('file_id', $fileId)
                    ->get();
                foreach ($bms as $bm) {
                    $loc = json_decode($bm->location ?? '', true);
                    if (!is_array($loc)) continue;
                    if (($loc['format'] ?? '') !== 'txt') continue;
                    if (!array_key_exists('chapterIndex', $loc)) continue;
                    $ci = (int)$loc['chapterIndex'];
                    $changed = false;
                    if ($merge === 'prev') {
                        if ($ci === $index) { $loc['chapterIndex'] = max(0, $index - 1); $changed = true; }
                        elseif ($ci > $index) { $loc['chapterIndex'] = $ci - 1; $changed = true; }
                    } else { // next
                        if ($ci === $index) { /* 目标并入下一个，新的承载章节会占用 index */ $loc['chapterIndex'] = $index; $changed = true; }
                        elseif ($ci > $index) { $loc['chapterIndex'] = $ci - 1; $changed = true; }
                    }
                    if ($changed) {
                        $bm->location = json_encode($loc, JSON_UNESCAPED_UNICODE);
                        $bm->save();
                    }
                }
            }
        });

        return ApiHelpers::success(['ok' => true], '', 200);
    }

    private function normalizePattern(?string $p): ?string
    {
        if (!$p) return null;
        $p = trim($p);
        // 如果看起来已有分隔符（以 / 开始且包含结束 /），直接使用；否则包裹并加上 miu
        if (strlen($p) > 2 && $p[0] === '/' && strrpos($p, '/') !== 0) {
            // 确保有 u 修饰符
            if (!preg_match('/\/[a-zA-Z]*u[a-zA-Z]*$/', $p)) {
                $p .= 'u';
            }
            return $p;
        }
        return '/'.$p.'/miu';
    }

    private function adjustToTitleStart(string $s, int $pos): int
    {
        $len = strlen($s);
        $i = max(0, $pos);
        while ($i < $len) {
            $c = $s[$i];
            // 跳过换行
            if ($c === "\n" || $c === "\r") { $i++; continue; }
            // 跳过 ASCII 空白
            if ($c === ' ' || $c === "\t") { $i++; continue; }
            // 跳过全角空格（UTF-8: E3 80 80）
            if ($i+2 < $len && $s[$i] === "\xE3" && $s[$i+1] === "\x80" && $s[$i+2] === "\x80") { $i += 3; continue; }
            break;
        }
        return $i;
    }

    private function extractLineTitle(string $s, int $start): ?string
    {
        $len = strlen($s);
        if ($start >= $len) return null;
        // 查找本行结束（支持 \r\n / \n / \r）
        $nlN = strpos($s, "\n", $start);
        $nlR = strpos($s, "\r", $start);
        $end = $len;
        if ($nlN !== false) $end = min($end, $nlN);
        if ($nlR !== false) $end = min($end, $nlR);
        $line = substr($s, $start, max(0, $end - $start));
        // 去掉首尾空白（含全角空格）
        $line = preg_replace('/^[\x{3000}\s]+|[\x{3000}\s]+$/u', '', $line ?? '');
        if ($line === null) return null;
        // 限制标题长度
        if (function_exists('mb_substr')) {
            $line = mb_substr($line, 0, 120, 'UTF-8');
        } else {
            $line = substr($line, 0, 240);
        }
        return $line !== '' ? $line : null;
    }

    /**
     * 将任意字节序列尽力转换为 UTF-8 文本，避免 JSON 编码时出现 Malformed UTF-8。
     */
    private function ensureUtf8(string $s): string
    {
        // 处理 BOM
        if (str_starts_with($s, "\xEF\xBB\xBF")) {
            $s = substr($s, 3);
        } elseif (str_starts_with($s, "\xFF\xFE")) {
            $s = function_exists('mb_convert_encoding') ? mb_convert_encoding($s, 'UTF-8', 'UTF-16LE') : $s;
        } elseif (str_starts_with($s, "\xFE\xFF")) {
            $s = function_exists('mb_convert_encoding') ? mb_convert_encoding($s, 'UTF-8', 'UTF-16BE') : $s;
        }

        // 已是合法 UTF-8
        if (function_exists('mb_detect_encoding') && mb_detect_encoding($s, 'UTF-8', true)) {
            return $s;
        }

        // 尝试常见编码到 UTF-8
        if (function_exists('mb_convert_encoding')) {
            $candidates = ['GB18030','GBK','BIG5','Windows-1252','ISO-8859-1'];
            foreach ($candidates as $enc) {
                $converted = @mb_convert_encoding($s, 'UTF-8', $enc);
                if ($converted !== false && ($converted === '' || mb_detect_encoding($converted, 'UTF-8', true))) {
                    return $converted;
                }
            }
        }

        // 最后兜底：忽略非法字节
        $ignored = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        return $ignored !== false ? $ignored : $s;
    }
}
