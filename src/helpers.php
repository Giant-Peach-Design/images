<?php

use Giantpeach\Schnapps\Images\Facades\Images;
use Giantpeach\Schnapps\Images\Compatibility\Images as CompatibilityImages;

/**
 * Helper function to get image URL with Glide parameters
 * 
 * @param int|string $image WordPress attachment ID or URL
 * @param array $params Glide parameters (w, h, fit, fm, etc.)
 * @return string
 */
function gp_image_url($image, array $params = []): string
{
    return Images::getUrl($image, $params);
}

/**
 * Helper function to create responsive image tag
 * 
 * @param int $imageId WordPress attachment ID
 * @param string $sizes Responsive sizes attribute (e.g., "(min-width: 768px) 50vw, 100vw")
 * @param array $widths Array of widths for srcset generation
 * @param array $attributes Additional HTML attributes
 * @param array $glideParams Additional Glide parameters (w, h, fit, etc.)
 * @return string HTML img tag
 */
function gp_image_tag(int $imageId, string $sizes = '100vw', array $widths = [375, 750, 1100, 1500, 2200], array $attributes = [], array $glideParams = []): string
{
    $imageTag = new \Giantpeach\Schnapps\Images\ImageTag();
    return $imageTag->create($imageId, $sizes, $widths, $attributes, $glideParams);
}

/**
 * Helper function to create picture tag for art direction
 * 
 * @param int|null $mobileImageId WordPress attachment ID for mobile (nullable)
 * @param int|null $desktopImageId WordPress attachment ID for desktop (nullable)
 * @param string $breakpoint Media query breakpoint (e.g., '640px')
 * @param array $mobileWidths Array of widths for mobile srcset
 * @param array $desktopWidths Array of widths for desktop srcset
 * @param array $attributes HTML attributes for the img tag
 * @param array $mobileGlideParams Additional Glide parameters for mobile
 * @param array $desktopGlideParams Additional Glide parameters for desktop
 * @param array $pictureAttributes HTML attributes for the picture tag
 * @return string HTML picture element
 */
function gp_picture_tag(?int $mobileImageId, ?int $desktopImageId, string $breakpoint = '640px', array $mobileWidths = [375, 750], array $desktopWidths = [1100, 1500, 2200], array $attributes = [], array $mobileGlideParams = [], array $desktopGlideParams = [], array $pictureAttributes = []): string
{
    $imageTag = new \Giantpeach\Schnapps\Images\ImageTag();
    return $imageTag->createPicture($mobileImageId, $desktopImageId, $breakpoint, $mobileWidths, $desktopWidths, $attributes, $mobileGlideParams, $desktopGlideParams, $pictureAttributes);
}

/**
 * Legacy helper functions for backwards compatibility
 * @deprecated version 3.0.0
 */

/**
 * Get image using legacy config-based sizes
 * 
 * @deprecated version 3.0.0 Use gp_image_url() instead
 */
function gp_get_image($image, $imageSize, $mobileImage = -1, $tabletImage = -1): array
{
    return CompatibilityImages::getInstance()->get($image, $imageSize, $mobileImage, $tabletImage);
}

/**
 * Get single image with legacy parameters
 * 
 * @deprecated version 3.0.0 Use gp_image_url() instead
 */
function gp_get_single_image($image, array $params = ['w' => 500, 'h' => 500, 'fit' => 'crop']): string
{
    return CompatibilityImages::getInstance()->getImage($image, $params);
}

/**
 * Get Glide image URL (legacy function name)
 * 
 * @deprecated version 3.0.0 Use gp_image_url() instead
 */
function gp_get_glide_image_url($image, array $params = []): string
{
    return CompatibilityImages::getInstance()->getGlideImageUrl($image, $params);
}

/**
 * Get image URL for config-based size
 * 
 * @deprecated version 3.0.0 Use gp_image_url() instead
 */
function gp_get_image_url_for_size($image, string $size): string
{
    return CompatibilityImages::getInstance()->getImageUrlForSize($image, $size);
}