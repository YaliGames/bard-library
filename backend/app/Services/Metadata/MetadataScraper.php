<?php

namespace App\Services\Metadata;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class MetadataScraper
{
    protected array $config;
    protected Client $client;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'headers' => $config['headers'] ?? [],
            'http_errors' => false,
            'timeout' => 30,
        ]);
    }

    /**
     * 规范化编码名称
     */
    protected function normalizeEncoding(string $encoding): string
    {
        $enc = strtolower(trim($encoding));
        $map = [
            'gbk' => 'GBK',
            'gb2312' => 'GBK',
            'gb18030' => 'GB18030',
            'utf-8' => 'UTF-8',
            'utf8' => 'UTF-8',
        ];
        return $map[$enc] ?? 'UTF-8';
    }

    /**
     * 兼容旧用法：仅返回首条详情
     */
    public function getMetadata(string $query): ?array
    {
        $urls = $this->searchDetailUrls($query, 1);
        if (empty($urls)) return null;
        return $this->fetchDetail($urls[0]);
    }

    /**
     * 搜索并返回前 N 条详情记录
     */
    public function searchItems(string $query, int $limit = 5): array
    {
        $urls = $this->searchDetailUrls($query, $limit);
        if (empty($urls)) {
            return [];
        }
        
        $promises = [];
        foreach ($urls as $idx => $url) {
            $promises[$idx] = $this->client->getAsync($url);
        }
        
        $items = [];
        $responses = \GuzzleHttp\Promise\Utils::settle($promises)->wait();
        
        foreach ($responses as $idx => $result) {
            if ($result['state'] === 'fulfilled') {
                try {
                    $rawHtml = $result['value']->getBody()->getContents();
                    $html = $this->convertToUtf8($rawHtml);
                    $item = $this->parseDetail($urls[$idx], $html);
                    if ($item) {
                        $items[] = $item;
                    }
                } catch (\Throwable $e) {
                    // 跳过失败项
                    Log::debug("[MetadataScraper] 解析详情失败: {$urls[$idx]}", ['error' => $e->getMessage()]);
                }
            }
        }
        
        return $items;
    }

    /**
     * 解析搜索页，提取前 N 个详情 URL
     */
    public function searchDetailUrls(string $query, int $limit = 5): array
    {
        $searchCfg = $this->config['search'] ?? null;
        if (!$searchCfg) {
            return [];
        }
        
        // 准备参数
        $params = $searchCfg['params'] ?? [];
        foreach ($params as $k => &$v) {
            $v = str_replace('$query', $query, (string)$v);
        }
        
        // 编码转换 (仅针对查询参数)
        $configEnc = $this->config['encoding'] ?? 'utf-8';
        $targetEnc = $this->normalizeEncoding($configEnc);
        
        // 如果目标网站使用 GBK/GB18030，需要转换查询参数
        if ($targetEnc !== 'UTF-8') {
            foreach ($params as $k => &$v) {
                if (is_string($v)) {
                    $v = mb_convert_encoding($v, $targetEnc, 'UTF-8');
                }
            }
        }
        
        // 发送请求
        $method = strtoupper($searchCfg['method'] ?? 'GET');
        $url = $searchCfg['url'] ?? '';
        if (!$url) {
            return [];
        }
        
        $res = $this->client->request($method, $url, ['query' => $params]);
        $rawHtml = $res->getBody()->getContents();
        
        // 检测并转换编码
        $html = $this->convertToUtf8($rawHtml);
        
        // 解析 HTML
        $crawler = new Crawler($html);
        $listXpath = $searchCfg['result_list_xpath'] ?? '';
        if (!$listXpath) {
            return [];
        }
        
        try {
            $nodes = $crawler->filterXPath($listXpath);
            if ($nodes->count() === 0) {
                return [];
            }
            
            $attr = $searchCfg['detail_url_attr'] ?? 'href';
            $urls = [];
            
            foreach ($nodes as $n) {
                $node = new Crawler($n);
                $u = $node->attr($attr);
                
                if (!empty($searchCfg['detail_url_parse']) && $searchCfg['detail_url_parse'] === 'extract_redirect_target') {
                    $u = $this->extractRedirectTarget($u);
                }
                
                // 规范化为绝对 URL
                $u = $this->normalizeUrl($u);
                
                if ($u) {
                    $urls[] = $u;
                    if (count($urls) >= $limit) break;
                }
            }
            
            return array_values(array_unique($urls));
        } catch (\Throwable $e) {
            Log::error("[MetadataScraper] XPath解析错误", ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function fetchDetail(string $url): array
    {
        $res = $this->client->request('GET', $url);
        $rawHtml = $res->getBody()->getContents();
        
        // 检测并转换编码
        $html = $this->convertToUtf8($rawHtml);
        
        return $this->parseDetail($url, $html);
    }

    /**
     * 解析详情页 HTML
     */
    protected function parseDetail(string $url, string $html): array
    {
        $crawler = new Crawler($html);
        $fields = $this->config['detail']['fields'] ?? [];
        $result = [];
        foreach ($fields as $field => $rule) {
            $value = null;
            
            // 支持正则表达式提取（直接从原始 HTML 中提取）
            $regex = $rule['regex'] ?? null;
            if ($regex) {
                if (preg_match($regex, $html, $matches)) {
                    $regexIndex = $rule['regex_index'] ?? 0;
                    $value = $matches[$regexIndex] ?? null;
                }
            } else {
                // 原有的 XPath 提取逻辑
                $xpath = $rule['xpath'] ?? null;
                if ($xpath) {
                    $nodes = $crawler->filterXPath($xpath);
                    $isMulti = !empty($rule['multi']);
                    if ($nodes->count() > 0) {
                        if ($isMulti) {
                            $vals = [];
                            foreach ($nodes as $n) {
                                $node = new Crawler($n);
                                $attr = $rule['attr'] ?? 'text';
                                $vals[] = ($attr !== 'text') ? $node->attr($attr) : trim($node->text());
                            }
                            $vals = array_values(array_unique(array_filter($vals, fn($v) => ($v !== null && $v !== ''))));
                            // 允许 pick=last 选取最后一个
                            $pick = $rule['pick'] ?? null; // 'last' | 'first'
                            if ($pick === 'last' && count($vals) > 0) {
                                $value = $vals[count($vals) - 1];
                            } else {
                                // 允许 join 指定分隔符拼接
                                $join = $rule['join'] ?? null;
                                $value = $join !== null ? implode((string)$join, $vals) : $vals;
                            }
                        } else {
                            $attr = $rule['attr'] ?? 'text';
                            $value = ($attr !== 'text') ? $nodes->first()->attr($attr) : trim($nodes->first()->text());
                        }
                    }
                }
            }
            
            if (isset($rule['filter'])) {
                $value = $this->applyFilter($rule['filter'], $value);
            }
            $result[$field] = $value;
        }
        // 规范化结果
        $result['url'] = $url;
        
        // 规范化 authors 字段
        if (isset($result['authors']) && !is_array($result['authors'])) {
            $a = trim((string)$result['authors']);
            $result['authors'] = $a === '' ? [] : preg_split('/[\s\/，,]+/u', $a);
        }
        
        // 生成 ID
        if (empty($result['id'])) {
            if (preg_match('/(\d{5,})/', $url, $m)) {
                $result['id'] = $m[1];
            } else {
                $result['id'] = substr(md5($url), 0, 12);
            }
        }
        $result['source'] = [
            'id' => $this->config['id'] ?? 'unknown',
            'description' => $this->config['name'] ?? '',
            'link' => $url,
        ];
        return $result;
    }

    /**
     * 转换HTML为UTF-8编码
     */
    protected function convertToUtf8(string $rawHtml): string
    {
        $detectedEnc = mb_detect_encoding($rawHtml, ['UTF-8', 'GB18030', 'GBK', 'ASCII'], true);
        
        if ($detectedEnc && $detectedEnc !== 'UTF-8') {
            $html = mb_convert_encoding($rawHtml, 'UTF-8', $detectedEnc);
            // 修改HTML中的charset声明，避免DomCrawler误判
            $html = preg_replace('/charset\s*=\s*["\']?(gb2312|gbk|gb18030)["\']?/i', 'charset="UTF-8"', $html);
            return $html;
        }
        
        return $rawHtml;
    }

    /**
     * 规范化URL为绝对路径
     */
    protected function normalizeUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        
        if (!preg_match('#^https?://#i', $url)) {
            $base = rtrim((string)($this->config['base_url'] ?? ''), '/');
            if ($base) {
                return $base . '/' . ltrim($url, '/');
            }
        }
        
        return $url;
    }

    protected function extractRedirectTarget(?string $url): ?string
    {
        if (!$url) {
            return $url;
        }
        
        if (preg_match('/\?url=([^&]+)/', $url, $m)) {
            return urldecode($m[1]);
        }
        
        return $url;
    }

    protected function applyFilter(string $filter, $value)
    {
        if ($value === null) {
            return $value;
        }
        
        // 使用 eval 执行 filter 表达式
        $v = $value;
        
        try {
            // 注册辅助函数到当前作用域
            $trim = fn($str) => is_string($str) ? trim($str) : $str;
            $floatval = fn($val) => floatval($val);
            $intval = fn($val) => intval($val);
            $strval = fn($val) => strval($val);
            $normalizeDate = fn($str) => is_string($str) ? preg_replace('/[^0-9\-]/', '', $str) : $str;
            
            // 正则匹配辅助函数
            $regexMatch = function($pattern, $subject, $index = 0) {
                if (preg_match($pattern, $subject, $matches)) {
                    return $matches[$index] ?? null;
                }
                return null;
            };
            
            $regexMatchAll = function($pattern, $subject, $index = 1) {
                if (preg_match_all($pattern, $subject, $matches)) {
                    return $matches[$index] ?? [];
                }
                return [];
            };
            
            $regexReplace = function($pattern, $replacement, $subject) {
                return preg_replace($pattern, $replacement, $subject);
            };
            
            // 数组处理辅助函数
            $arrayMap = fn($callback, $array) => is_array($array) ? array_map($callback, $array) : $array;
            $arrayFilter = fn($callback, $array) => is_array($array) ? array_filter($array, $callback) : $array;
            $arrayUnique = fn($array) => is_array($array) ? array_values(array_unique($array)) : $array;
            $arrayValues = fn($array) => is_array($array) ? array_values($array) : $array;
            $arrayKeys = fn($array) => is_array($array) ? array_keys($array) : $array;
            $arrayFirst = fn($array) => is_array($array) && !empty($array) ? reset($array) : null;
            $arrayLast = fn($array) => is_array($array) && !empty($array) ? end($array) : null;
            
            // 字符串处理辅助函数
            $strContains = fn($haystack, $needle) => is_string($haystack) && strpos($haystack, $needle) !== false;
            $strSplit = fn($delimiter, $string) => is_string($string) ? explode($delimiter, $string) : $string;
            $strJoin = fn($glue, $array) => is_array($array) ? implode($glue, $array) : $array;
            
            // 执行 filter 表达式
            $result = eval("return $filter;");
            return $result;
        } catch (\Throwable $e) {
            Log::warning("[MetadataScraper] Filter 执行失败: $filter", [
                'error' => $e->getMessage(),
                'value' => $value
            ]);
            return $value;
        }
    }
}
