<?php

namespace Giantpeach\Schnapps\Images;

use Giantpeach\Schnapps\Config\Facades\Config;
use League\Glide\ServerFactory;

class Images
{
  protected static $instance;
  /**
   * The base path for the image URL.
   * 
   * You may need to update your nginx conf, or htaccess, e.g.
   * 
   * location ~ \/img/(.+\.(png|jpg|jpeg|gif|ico))$ {
   *   try_files $uri $uri/ /index.php$is_args$args;
   * }
   *
   * @var string
   */
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
   * Set the config
   *
   * @param array $config
   * @return void
   */
  public function config($config)
  {
    $this->config = array_merge($this->config, $config);
  }

  /**
   * Get the singleton instance
   *
   * @return Images
   */
  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new Images();
    }

    return self::$instance;
  }

  /**
   * Handle the request, output the image
   *
   * @return void
   */
  public function handle()
  {
    if (strpos($_SERVER['REQUEST_URI'], $this->basePath) === false) {
      return;
    }

    $this->outputImage($_SERVER['REQUEST_URI']);
  }

  /**
   * Generate a Glide image URL from wordpress image ID or URL
   * 
   * @param $image int|string
   * @param array $params
   * @return string
   */
  public function getGlideImageUrl($image, $params = [])
  {
    $url = "";

    if (is_int($image)) {
      $url = $image = wp_get_attachment_url($image);
    }

    if (is_string($image)) {
      $url = $image;
    }

    if (empty($url)) {
      return '';
    }

    /**
     * If we're an SVG just return standard URL
     */
    if (pathinfo($url, PATHINFO_EXTENSION) === 'svg') {
      return $url;
    }

    $path = $this->getRelativeFilePathFromUrl($url);

    return home_url() . '/' . $this->basePath . $path . '?' . http_build_query($params);
  }

  /**
   * Output the image using the Glide server
   *
   * @param string $url
   * @return void
   */
  public function outputImage($url)
  {
    $url = parse_url($url);

    // strip out the first occurance of base path from the url.
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
   * Get the relative file path from the URL
   *
   * @param string $url
   * @return string
   */
  public function getRelativeFilePathFromUrl($url): string
  {
    $url = str_replace($this->uploadsDir['baseurl'], '', $url);
    return $url;
  }

  /**
   * Retrieves the image for a single device.
   *
   * @deprecated This method is deprecated and will be removed in future versions.
   * Please use the `get` method instead.
   *
   * @param int|string $image 
   * @param array $params 
   * @return array The image for a single device.
   */
  public function getImage(int|string $image, array $params = ['w' => 500, 'h' => 500, 'crop' => true])
  {
    $arr = [
      'url' => $this->getGlideImageUrl($image, $params),
      //'width' => $params['w'],
      //'height' => $params['h'],
      'webp' => $this->getGlideImageUrl($image, array_merge($params, ['fm' => 'webp'])),
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
   * Retrieves the images for different devices.
   *
   * @deprecated This method is deprecated and will be removed in future versions.
   * Please use the `get` method instead.
   *
   * @param array $desktop 
   * @param array $mobile 
   * @param array $tablet 
   * @return array The images for different devices.
   */
  public function getImages(array $desktop, array $mobile = [], array $tablet = [])
  {
    $arr = [
      'desktop' => $desktop['id'] ? $this->getImage($desktop['id'], $desktop['params']) : null,
    ];

    if (count($mobile) > 0) {
      $arr['mobile'] = $mobile['id'] ? $this->getImage($mobile['id'], $mobile['params']) : null;
    }

    if (count($tablet) > 0) {
      $arr['tablet'] = $mobile['id'] ? $this->getImage($tablet['id'], $tablet['params']) : null;
    }

    return $arr;
  }


  /**
   * Generates an image with specified parameters.
   *
   * @param int|string $image The image to generate.
   * @param array $params The parameters for generating the image. Default values: ['w' => 500, 'h' => 500, 'crop' => true]
   * @return array The generated image details.
   */
  private function image(int|string $image, array $params = ['w' => 500, 'h' => 500, 'crop' => true])
  {
    $arr = [
      'url' => $this->getGlideImageUrl($image, $params),
      //'width' => $params['w'],
      //'height' => $params['h'],
      'webp' => $this->getGlideImageUrl($image, array_merge($params, ['fm' => 'webp'])),
      'alt' => get_post_meta($image, '_wp_attachment_image_alt', true),
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
   * Generates an array of images based on the provided IDs and parameters.
   *
   * @param int|string $desktopId The ID of the desktop image.
   * @param array $desktopParams The parameters for the desktop image.
   * @param int|string $mobileId The ID of the mobile image. Default is -1.
   * @param array $mobileParams The parameters for the mobile image.
   * @param int|string $tabletId The ID of the tablet image. Default is -1.
   * @param array $tabletParams The parameters for the tablet image.
   * @return array The generated array of images.
   */
  private function images(int|string $desktopId, array $desktopParams, int|string $mobileId = -1, array $mobileParams = [], int|string $tabletId = -1, array $tabletParams = [])
  {
    $arr = [];

    if ($desktopId > 0) {
      $arr['desktop'] = $desktopId ? $this->image($desktopId, $desktopParams) : null;
    }

    if ($mobileId > 0) {
      $arr['mobile'] = $mobileId ? $this->image($mobileId, $mobileParams) : null;
    }

    if ($tabletId > 0) {
      $arr['tablet'] = $tabletId ? $this->image($tabletId, $tabletParams) : null;
    }

    return $arr;
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
   */
  public function get(int|string $image, string|array $imageSize, int|string $mobileImage = -1, int|string $tabletImage = -1)
  {
    $imageSizes = Config::get('image-sizes');

    if (!isset($imageSizes[$imageSize])) {
      throw new \Exception("Image size $imageSize not found. Have you added it to the config?");
    }

    $imgSize = $imageSizes[$imageSize];

    // we need to check if the imgSize array has a 'w' and 'h' key...
    if (isset($imgSize['w']) || isset($imgSize['h'])) {
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
}

//Images::getInstance();

function gp_get_image_url($image, $params = [])
{
  return Images::getInstance()->getGlideImageUrl($image, $params);
}
