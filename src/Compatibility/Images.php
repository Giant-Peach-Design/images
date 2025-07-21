<?php

namespace Giantpeach\Schnapps\Images\Compatibility;

use Giantpeach\Schnapps\Config\Facades\Config;
use Giantpeach\Schnapps\Images\Images as ModernImages;

/**
 * Compatibility wrapper for Images class
 *
 * Provides backwards compatibility for methods and patterns that were removed in v3.0
 *
 * @deprecated version 3.0.0 Use Giantpeach\Schnapps\Images\Images instead
 */
class Images
{
    protected static $instance;
    protected $modernImages;
    protected $config = [];
    protected $imageSizes = [];

    private function __construct()
    {
        $this->modernImages = ModernImages::getInstance();
        $this->loadDefaultImageSizes();
    }

    /**
     * Get the singleton instance
     *
     * @deprecated version 3.0.0 Use ModernImages::getInstance() instead
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Configure the Images instance
     *
     * @deprecated version 3.0.0 Use ModernImages::config() instead
     */
    public function config(array $config): void
    {
        $this->config = array_merge($this->config, $config);

        // Pass through to modern Images
        $this->modernImages->config($config);

        // Handle image sizes config
        if (isset($config['image-sizes'])) {
            $this->imageSizes = array_merge($this->imageSizes, $config['image-sizes']);
        }
    }

    /**
     * Get Glide image URL (legacy method name)
     *
     * @deprecated version 3.0.0 Use getUrl() instead
     */
    public function getGlideImageUrl($image, array $params = []): string
    {
        return $this->modernImages->getUrl($image, $params);
    }

    /**
     * Get image using config-based sizes and multi-viewport handling
     *
     * @deprecated version 3.0.0 Use getUrl() with explicit parameters instead
     */
    public function get($image, $imageSize, $mobileImage = -1, $tabletImage = -1): array
    {
        $result = [
            'desktop' => '',
            'mobile' => '',
            'tablet' => ''
        ];

        $sizeConfig = $this->getSizeFromConfig($imageSize);

        if ($sizeConfig) {
            // Desktop image
            if (isset($sizeConfig['desktop'])) {
                $result['desktop'] = $this->modernImages->getUrl($image, $sizeConfig['desktop']);
            }

            // Mobile image
            $mobileImageId = ($mobileImage !== -1) ? $mobileImage : $image;
            if (isset($sizeConfig['mobile'])) {
                $result['mobile'] = $this->modernImages->getUrl($mobileImageId, $sizeConfig['mobile']);
            }

            // Tablet image
            $tabletImageId = ($tabletImage !== -1) ? $tabletImage : $image;
            if (isset($sizeConfig['tablet'])) {
                $result['tablet'] = $this->modernImages->getUrl($tabletImageId, $sizeConfig['tablet']);
            }
        }

        return $result;
    }

    /**
     * Get single image with default parameters
     *
     * @deprecated version 3.0.0 Use getUrl() instead
     */
    public function getImage($image, array $params = ['w' => 500, 'h' => 500, 'fit' => 'crop']): string
    {
        return $this->modernImages->getUrl($image, $params);
    }

    /**
     * Get multi-viewport images array
     *
     * @deprecated version 3.0.0 Use getUrl() for each viewport instead
     */
    public function getImages($desktop, array $mobile = [], array $tablet = []): array
    {
        $result = [
            'desktop' => '',
            'mobile' => '',
            'tablet' => ''
        ];

        if (is_array($desktop)) {
            $result['desktop'] = $this->modernImages->getUrl($desktop['image'] ?? '', $desktop['params'] ?? []);
        } else {
            $result['desktop'] = $this->modernImages->getUrl($desktop, []);
        }

        if (!empty($mobile)) {
            $result['mobile'] = $this->modernImages->getUrl($mobile['image'] ?? '', $mobile['params'] ?? []);
        }

        if (!empty($tablet)) {
            $result['tablet'] = $this->modernImages->getUrl($tablet['image'] ?? '', $tablet['params'] ?? []);
        }

        return $result;
    }

    /**
     * Get image URL for config-based size
     *
     * @deprecated version 3.0.0 Use getUrl() with explicit parameters instead
     */
    public function getImageUrlForSize($image, string $size): string
    {
        $sizeConfig = $this->getSizeFromConfig($size);

        if ($sizeConfig && isset($sizeConfig['desktop'])) {
            return $this->modernImages->getUrl($image, $sizeConfig['desktop']);
        }

        return $this->modernImages->getUrl($image, []);
    }

    /**
     * Get the size configuration for an image.
     *
     * @deprecated version 3.0.0
     * @param string $size The size of the image.
     * @return array The size configuration for the image.
     */
    protected function getSizeFromConfig(string $size): array
    {
        // First try to load from Config package if available
        if (class_exists('Giantpeach\Schnapps\Config\Config')) {
            $imageSizes = \Giantpeach\Schnapps\Config\Config::get('image-sizes');

            if (isset($imageSizes)) {
                return $this->processSizeFromConfig($imageSizes, $size);
            }
        }

        // Fallback to local config
        return $this->processSizeFromConfig($this->imageSizes, $size);
    }

    /**
     * Process size configuration with nested key support
     *
     * @param array $imageSizes The image sizes configuration
     * @param string $size The size key (supports nested like 'hero.desktop')
     * @return array The size configuration
     */
    protected function processSizeFromConfig(array $imageSizes, string $size): array
    {
        $isNested = false;
        $topLevelKey = $size;

        if (!isset($imageSizes)) {
            trigger_error("Image Size config not found. Have you created it?", E_USER_WARNING);
            return [];
        }

        // check if size contains a dot, if so, assume it's a nested key
        // e.g. 'hero.desktop' or 'hero.mobile'
        if (strpos($size, '.') !== false) {
            $isNested = true;
            $keys = explode('.', $size);
            $topLevelKey = $keys[0];
        }

        if (!$isNested && !isset($imageSizes[$topLevelKey])) {
            trigger_error("Image size $topLevelKey not found. Have you added it to the config?", E_USER_WARNING);
            return [];
        }

        $imgSize = $imageSizes[$topLevelKey];

        if ($isNested) {
            $keys = explode('.', $size);
            $imgSize = $imageSizes[$keys[0]][$keys[1]];
        }

        if (!isset($imgSize['desktop'])) {
            // if array doesn't contain a desktop key, assume it's a single size
            return $imgSize;
        }

        return $imgSize;
    }

    /**
     * Load default image sizes for backwards compatibility
     */
    protected function loadDefaultImageSizes(): void
    {
        $this->imageSizes = [
            'thumbnail' => [
                'desktop' => ['w' => 300, 'h' => 300, 'fit' => 'crop'],
                'mobile' => ['w' => 150, 'h' => 150, 'fit' => 'crop'],
                'tablet' => ['w' => 225, 'h' => 225, 'fit' => 'crop']
            ],
            'medium' => [
                'desktop' => ['w' => 600, 'h' => 400, 'fit' => 'crop'],
                'mobile' => ['w' => 300, 'h' => 200, 'fit' => 'crop'],
                'tablet' => ['w' => 450, 'h' => 300, 'fit' => 'crop']
            ],
            'large' => [
                'desktop' => ['w' => 1200, 'h' => 800, 'fit' => 'crop'],
                'mobile' => ['w' => 600, 'h' => 400, 'fit' => 'crop'],
                'tablet' => ['w' => 900, 'h' => 600, 'fit' => 'crop']
            ],
            'hero' => [
                'desktop' => ['w' => 1920, 'h' => 1080, 'fit' => 'crop'],
                'mobile' => ['w' => 768, 'h' => 432, 'fit' => 'crop'],
                'tablet' => ['w' => 1024, 'h' => 576, 'fit' => 'crop']
            ]
        ];
    }

    /**
     * Pass through method calls to modern Images instance
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists($this->modernImages, $method)) {
            return call_user_func_array([$this->modernImages, $method], $arguments);
        }

        throw new \BadMethodCallException("Method {$method} does not exist");
    }
}
