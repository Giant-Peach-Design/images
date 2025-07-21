<?php

namespace Giantpeach\Schnapps\Images;

class ImageTag
{
    protected Images $images;
    
    public function __construct()
    {
        $this->images = Images::getInstance();
    }
    
    /**
     * Creates an HTML img tag with responsive srcset, similar to Liquid's image_tag filter.
     * 
     * @param int $imageId The WordPress attachment ID
     * @param string $sizes The sizes attribute for responsive images (e.g., "(min-width: 768px) 50vw, 100vw")
     * @param array $widths Array of widths to generate in the srcset (e.g., [375, 750, 1100, 1500, 2200])
     * @param array $attributes Additional HTML attributes for the img tag
     * @return string The HTML img tag
     */
    public function create(int $imageId, string $sizes = '100vw', array $widths = [375, 750, 1100, 1500, 2200], array $attributes = []): string
    {
        // Get the original image URL and metadata
        $originalUrl = wp_get_attachment_url($imageId);
        if (!$originalUrl) {
            return '';
        }

        // Get image metadata
        $metadata = wp_get_attachment_metadata($imageId);
        $alt = get_post_meta($imageId, '_wp_attachment_image_alt', true) ?: '';
        
        // If SVG, return simple img tag
        if (pathinfo($originalUrl, PATHINFO_EXTENSION) === 'svg') {
            $attrs = array_merge(['src' => $originalUrl, 'alt' => $alt], $attributes);
            return $this->buildImgTag($attrs);
        }

        // Build srcset
        $srcsetEntries = [];
        $maxWidth = $metadata['width'] ?? 3000;
        
        foreach ($widths as $width) {
            // Skip widths larger than the original image
            if ($width > $maxWidth) {
                continue;
            }
            
            // Generate URL for this width
            $url = $this->images->getGlideImageUrl($imageId, ['w' => $width]);
            $srcsetEntries[] = $url . ' ' . $width . 'w';
            
            // Also generate WebP version
            $webpUrl = $this->images->getGlideImageUrl($imageId, ['w' => $width, 'fm' => 'webp']);
            $srcsetEntries[] = $webpUrl . ' ' . $width . 'w';
        }
        
        // Use the middle width as the default src
        $defaultWidth = $widths[floor(count($widths) / 2)] ?? 1100;
        $defaultSrc = $this->images->getGlideImageUrl($imageId, ['w' => $defaultWidth]);
        
        // Build attributes
        $imgAttributes = array_merge([
            'src' => $defaultSrc,
            'srcset' => implode(', ', $srcsetEntries),
            'sizes' => $sizes,
            'alt' => $alt,
            'loading' => 'lazy',
            'decoding' => 'async',
        ], $attributes);
        
        // Add width and height for aspect ratio
        if (isset($metadata['width']) && isset($metadata['height'])) {
            if (!isset($imgAttributes['width'])) {
                $imgAttributes['width'] = $metadata['width'];
            }
            if (!isset($imgAttributes['height'])) {
                $imgAttributes['height'] = $metadata['height'];
            }
        }
        
        return $this->buildImgTag($imgAttributes);
    }
    
    /**
     * Helper method to build an img tag from attributes
     * 
     * @param array $attributes
     * @return string
     */
    protected function buildImgTag(array $attributes): string
    {
        $html = '<img';
        foreach ($attributes as $attr => $value) {
            if ($value !== null && $value !== '') {
                $html .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
            }
        }
        $html .= '>';
        
        return $html;
    }
}