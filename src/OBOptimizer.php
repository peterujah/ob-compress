<?php
namespace Peterujah\NanoBlock;
/**
 * OBCompress - A simple php class to optimize php page performance
 * @author      Peter Chigozie(NG) peterujah
 * @copyright   Copyright (c), 2021 Peter(NG) peterujah
 * @license     MIT public license
 */
class OBOptimizer {
    /**
     * @var string $cacheDir The directory where cached files will be stored.
     */
    private $cacheDir;

    /**
     * @var int $cacheExpiration The expiration time for cached files in seconds (default: 600 seconds, i.e., 10 minutes).
     */
    private $cacheExpiration;

    /**
     * Class constructor.
     *
     * @param int $cacheExpiration The expiration time for cached files in seconds (default: 600 seconds, i.e., 10 minutes).
     * @param string $cacheDir The directory where cached files will be stored (default: 'cache').
     */
    public function __construct($cacheExpiration = 60 * 60 * 24, $cacheDir = 'cache') {
        $this->cacheDir = $cacheDir;
        $this->cacheExpiration = $cacheExpiration;
    }

    /**
     * Get the file path for the cache based on the current request URI.
     *
     * @return string The file path for the cache.
     */
    public function getCacheLocation() {
        return rtrim($this->cacheDir, "/") . '/' . md5($this->getUrl()) . '.html';
    }

    public function getCacheFilepath() {
        return rtrim($this->cacheDir, "/") . '/';
    }

    /**
     * Check if the cached file is still valid based on its expiration time.
     *
     * @param string $cacheFile The path to the cached file.
     * @return bool True if the cache is still valid; false otherwise.
     */
    public function isCacheValid($cacheFile) {
        return file_exists($cacheFile) && time() - filemtime($cacheFile) < $this->cacheExpiration;
    }

    /**
     * Load the content from the cache file and exit the script.
     *
     * @param string $cacheFile The path to the cached file.
     */
    public function loadFromCache($cacheFile) {
        @readfile($cacheFile);
        exit;
    }

    /**
     * Save the content to the cache file.
     *
     * @param string $cacheFile The path to the cached file.
     * @param string $content The content to be saved to the cache file.
     */
    public function saveToCache($cacheFile, $content) {
        if (!@file_exists($this->getCacheFilepath())){
			@mkdir($this->getCacheFilepath(), 0755, true);
		}
        @file_put_contents($cacheFile, $content);
        return $content;
    }

    /**
     * Get current page url
     *
     * @param string $url The content to be saved to the cache file.
     */
    private function getUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
}
