<?php

use Giantpeach\Schnapps\Images\Facades\Images;

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
 * @return string HTML img tag
 */
function gp_image_tag(int $imageId, string $sizes = '100vw', array $widths = [375, 750, 1100, 1500, 2200], array $attributes = []): string
{
    return Images::createImageTag($imageId, $sizes, $widths, $attributes);
}

/**
 * Helper function to create picture tag for art direction
 * 
 * @param int $mobileImageId WordPress attachment ID for mobile
 * @param int $desktopImageId WordPress attachment ID for desktop
 * @param string $breakpoint Media query breakpoint (e.g., '640px')
 * @param array $mobileWidths Array of widths for mobile srcset
 * @param array $desktopWidths Array of widths for desktop srcset
 * @param array $attributes HTML attributes for the img tag
 * @return string HTML picture element
 */
function gp_picture_tag(int $mobileImageId, int $desktopImageId, string $breakpoint = '640px', array $mobileWidths = [375, 750], array $desktopWidths = [1100, 1500, 2200], array $attributes = []): string
{
    return Images::createPictureTag($mobileImageId, $desktopImageId, $breakpoint, $mobileWidths, $desktopWidths, $attributes);
}