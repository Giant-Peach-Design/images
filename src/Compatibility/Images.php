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
   * Retrieves the image based on the specified parameters.
   *
   * @param int|string $image The ID or URL of the image.
   * @param string|array $imageSize The size of the image to retrieve.
   * @param int|string $mobileImage The ID or URL of the mobile image (optional).
   * @param int|string $tabletImage The ID or URL of the tablet image (optional).
   * @return mixed The retrieved image.
   * @throws \Exception If the specified image size is not found in the configuration.
   * @deprecated version 3.0.0 Use getUrl() with explicit parameters instead
   */
  public function get(int|string $image, string|array $imageSize, int|string $mobileImage = -1, int|string $tabletImage = -1)
  {
    $imgSize = $this->getSizeFromConfig($imageSize);

    // we need to check if the imgSize array has a 'w' and 'h' key...
    if (!isset($imgSize['desktop'])) {
      return $this->images(
        desktopId: $image,
        desktopParams: $imgSize,
      );
    }

    // or if it's an array of sizes (desktop, mobile, tablet)...
    if (isset($imgSize['desktop'])) {
      return $this->images(
        desktopId: $image,
        tabletId: $tabletImage,
        mobileId: $mobileImage,
        desktopParams: $imgSize['desktop'],
        mobileParams: $imgSize['mobile'] ?? [],
        tabletParams: $imgSize['tablet'] ?? []
      );
    }
  }

  /**
   * Generate images array for multiple device sizes (private method from original)
   * 
   * @deprecated version 3.0.0
   */
  private function images(
    int|string $desktopId,
    array $desktopParams,
    int|string $mobileId = -1,
    array $mobileParams = [],
    int|string $tabletId = -1,
    array $tabletParams = []
  ): array {
    $result = [
      'desktop' => '',
      'mobile' => '',
      'tablet' => ''
    ];

    // Desktop image
    if ($desktopId !== -1 && !empty($desktopParams)) {
      $result['desktop'] = $this->image($desktopId, $desktopParams);
    }

    // Mobile image (fallback to desktop if not provided)
    $finalMobileId = ($mobileId !== -1) ? $mobileId : $desktopId;
    if ($finalMobileId !== -1 && !empty($mobileParams)) {
      $result['mobile'] = $this->image($finalMobileId, $mobileParams);
    }

    // Tablet image (fallback to desktop if not provided)  
    $finalTabletId = ($tabletId !== -1) ? $tabletId : $desktopId;
    if ($finalTabletId !== -1 && !empty($tabletParams)) {
      $result['tablet'] = $this->image($finalTabletId, $tabletParams);
    }

    return $result;
  }

  /**
   * Generate single image data with url, webp, width, height, and alt (private method from original)
   * 
   * @deprecated version 3.0.0
   */
  private function image(int|string $image, array $params = ['w' => 500, 'h' => 500, 'crop' => true]): array
  {
    $arr = [
      'url' => $this->getGlideImageUrl($image, $params),
      'webp' => $this->getGlideImageUrl($image, array_merge($params, ['fm' => 'webp'])),
      'alt' => get_post_meta($image, '_wp_attachment_image_alt', true)
    ];

    if (isset($params['w'])) {
      $arr['width'] = $params['w'];
    }

    if (isset($params['h'])) {
      $arr['height'] = $params['h'];
    }

    return $arr;
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
      $desktopId = $desktop['id'];

      if (array_key_exists('image', $desktop)) {
        $desktopId = $desktop['image'];
      }

      $result['desktop'] = $this->image($desktopId ?? '', $desktop['params'] ?? []);
    } else {
      $result['desktop'] = $this->image($desktop, []);
    }

    if (!empty($mobile)) {
      $mobileId = $mobile['id'];

      if (array_key_exists('image', $mobile)) {
        $mobileId = $mobile['image'];
      }

      $result['mobile'] = $this->image($mobile['image'] ?? '', $mobile['params'] ?? []);
    }

    if (!empty($tablet)) {
      $result['tablet'] = $this->image($tablet['image'] ?? '', $tablet['params'] ?? []);
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
      $imageSizes = Config::get('image-sizes');

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
