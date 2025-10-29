<?php

namespace App\Services\Metadata;

use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Cache;

class MetadataConfigLoader
{
    protected string $configDir;
    protected int $cacheTtl = 600; // seconds

    public function __construct(?string $configDir = null)
    {
        $this->configDir = $configDir ?: base_path('config/metadata');
    }

    /**
     * 加载指定平台配置（YAML）
     */
    public function load(string $platform): ?array
    {
        $cacheKey = 'metadata_config_' . $platform;
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($platform) {
            $file = $this->configDir . DIRECTORY_SEPARATOR . $platform . '.yaml';
            if (!file_exists($file)) {
                return null;
            }
            $config = Yaml::parseFile($file) ?? [];
            // 简单校验
            if (!is_array($config)) return null;
            if (empty($config['id'])) $config['id'] = $platform;
            return $config;
        });
    }

    /**
     * 列出所有可用平台 id（文件名）
     */
    public function listPlatforms(): array
    {
        $files = glob($this->configDir . DIRECTORY_SEPARATOR . '*.yaml') ?: [];
        return array_values(array_map(fn($f) => basename($f, '.yaml'), $files));
    }
}
