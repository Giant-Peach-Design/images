<?php

namespace Giantpeach\Schnapps\Images;

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


  public function getImage(int|string $image, array $params = ['w' => 500, 'h' => 500, 'crop' => true])
  {
    $arr = [
      'url' => $this->getGlideImageUrl($image, $params),
      'width' => $params['w'],
      'height' => $params['h'],
      'webp' => $this->getGlideImageUrl($image, array_merge($params, ['fm' => 'webp'])),
    ];

    return $arr;
  }

  public function getImages(array $desktop, array $mobile = [], array $tablet = [])
  {
    $arr = [
      'desktop' => $this->getImage($desktop['id'], $desktop['params']),
    ];

    if (count($mobile) > 0) {
      $arr['mobile'] = $this->getImage($mobile['id'], $mobile['params']);
    }

    if (count($tablet) > 0) {
      $arr['tablet'] = $this->getImage($tablet['id'], $tablet['params']);
    }

    return $arr;
  }
}

//Images::getInstance();

function gp_get_image_url($image, $params = [])
{
  return Images::getInstance()->getGlideImageUrl($image, $params);
}
