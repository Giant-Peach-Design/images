<?php

namespace Giantpeach\Schnapps\Images;

use League\Glide\ServerFactory;

class Images
{
    protected static $instance;
    protected $basePath = 'img';
    protected $uploadsDir;
    protected $cacheDir;
    protected $config;

    private function __construct()
    {
        $this->uploadsDir = wp_upload_dir();
        $this->cacheDir = wp_upload_dir()['basedir'] . '/cache';

        $this->config = [
            'source' => $this->uploadsDir['basedir'],
            'cache' => $this->cacheDir,
        ];

        add_action('init', [$this, 'handle']);
    }

    /**
     * Get the singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new Images();
        }

        return self::$instance;
    }

    /**
     * Set additional config options for Glide
     */
    public function config(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Handle the Glide image request
     */
    public function handle(): void
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->basePath) === false) {
            return;
        }

        $this->outputImage($_SERVER['REQUEST_URI']);
    }

    /**
     * Generate a Glide image URL from WordPress image ID or URL
     *
     * @param int|string $image WordPress attachment ID or URL
     * @param array $params Glide parameters (w, h, fit, fm, etc.)
     * @return string
     */
    public function getUrl($image, array $params = []): string
    {
        $url = "";

        if (is_int($image)) {
            $url = wp_get_attachment_url($image);
        }

        if (is_string($image)) {
            $url = $image;
        }

        if (empty($url)) {
            return '';
        }

        // If SVG, return as-is
        if (pathinfo($url, PATHINFO_EXTENSION) === 'svg') {
            return $url;
        }

        $path = $this->getRelativeFilePathFromUrl($url);

        return get_option('home') . '/' . $this->basePath . $path . '?' . http_build_query($params);
    }

    /**
     * Create responsive image tag with srcset
     */
    public function createImageTag(int $imageId, string $sizes = '100vw', array $widths = [375, 750, 1100, 1500, 2200], array $attributes = []): string
    {
        $imageTag = new ImageTag();
        return $imageTag->create($imageId, $sizes, $widths, $attributes);
    }

    /**
     * Create picture tag for art direction
     */
    public function createPictureTag(
        int $mobileImageId, 
        int $desktopImageId, 
        string $breakpoint = '640px',
        array $mobileWidths = [375, 750],
        array $desktopWidths = [1100, 1500, 2200],
        array $attributes = []
    ): string {
        $imageTag = new ImageTag();
        return $imageTag->createPicture($mobileImageId, $desktopImageId, $breakpoint, $mobileWidths, $desktopWidths, $attributes);
    }

    /**
     * Output the image using Glide server
     */
    protected function outputImage(string $url): void
    {
        $url = parse_url($url);

        $pos = strpos($url['path'], $this->basePath);
        if ($pos !== false) {
            $path = substr_replace($url['path'], '', $pos, strlen($this->basePath) + 1);

            $server = ServerFactory::create($this->config);
            status_header(200);
            $server->outputImage($path, $_GET);
            die();
        }
    }

    /**
     * Get relative file path from URL
     */
    protected function getRelativeFilePathFromUrl(string $url): string
    {
        return str_replace($this->uploadsDir['baseurl'], '', $url);
    }
}