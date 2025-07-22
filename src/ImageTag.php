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
     * @param array $glideParams Additional Glide parameters (w, h, fit, etc.)
     * @return string The HTML img tag
     */
    public function create(int $imageId, string $sizes = '100vw', array $widths = [375, 750, 1100, 1500, 2200], array $attributes = [], array $glideParams = []): string
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
            
            // Merge width with custom Glide parameters
            $params = array_merge($glideParams, ['w' => $width]);
            
            // Generate URL for this width
            $url = $this->images->getUrl($imageId, $params);
            $srcsetEntries[] = $url . ' ' . $width . 'w';
            
            // Also generate WebP version
            $webpParams = array_merge($params, ['fm' => 'webp']);
            $webpUrl = $this->images->getUrl($imageId, $webpParams);
            $srcsetEntries[] = $webpUrl . ' ' . $width . 'w';
        }
        
        // Use the middle width as the default src
        $defaultWidth = $widths[floor(count($widths) / 2)] ?? 1100;
        $defaultParams = array_merge($glideParams, ['w' => $defaultWidth]);
        $defaultSrc = $this->images->getUrl($imageId, $defaultParams);
        
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
     * Creates a picture tag for art direction with different images per viewport
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
     * @return string The HTML picture element
     */
    public function createPicture(
        ?int $mobileImageId, 
        ?int $desktopImageId, 
        string $breakpoint = '640px',
        array $mobileWidths = [375, 750],
        array $desktopWidths = [1100, 1500, 2200],
        array $attributes = [],
        array $mobileGlideParams = [],
        array $desktopGlideParams = [],
        array $pictureAttributes = []
    ): string {
        
        // Return empty if both images are null
        if ($mobileImageId === null && $desktopImageId === null) {
            return '';
        }
        
        // If only one image is provided, use regular img tag instead of picture
        if ($mobileImageId === null || $desktopImageId === null) {
            $imageId = $desktopImageId ?? $mobileImageId;
            $widths = $desktopImageId ? $desktopWidths : $mobileWidths;
            $glideParams = $desktopImageId ? $desktopGlideParams : $mobileGlideParams;
            
            return $this->create($imageId, '100vw', $widths, $attributes, $glideParams);
        }
        
        // Get alt text (prefer desktop, fallback to mobile)
        $alt = get_post_meta($desktopImageId, '_wp_attachment_image_alt', true) ?: 
               get_post_meta($mobileImageId, '_wp_attachment_image_alt', true) ?: '';
        
        // Auto-derive widths from Glide parameters if not provided
        $finalMobileWidths = $this->deriveWidthsFromParams($mobileWidths, $mobileGlideParams);
        $finalDesktopWidths = $this->deriveWidthsFromParams($desktopWidths, $desktopGlideParams);
        
        // Build mobile srcset
        $mobileSrcset = $this->buildSrcsetForImage($mobileImageId, $finalMobileWidths, $mobileGlideParams);
        
        // Build desktop srcset  
        $desktopSrcset = $this->buildSrcsetForImage($desktopImageId, $finalDesktopWidths, $desktopGlideParams);
        
        // Get default src (middle width of desktop)
        $defaultWidth = $finalDesktopWidths[floor(count($finalDesktopWidths) / 2)] ?? 1100;
        $defaultParams = array_merge($desktopGlideParams, ['w' => $defaultWidth]);
        $defaultSrc = $this->images->getUrl($desktopImageId, $defaultParams);
        
        // Build picture element with attributes
        $html = $this->buildPictureTag($pictureAttributes);
        
        // Mobile source
        if (!empty($mobileSrcset)) {
            $html .= '<source media="(max-width: ' . $breakpoint . ')" srcset="' . esc_attr(implode(', ', $mobileSrcset)) . '">';
        }
        
        // Desktop img (fallback) with default classes
        $defaultAttributes = [
            'class' => 'w-full h-full object-cover'
        ];
        
        $imgAttributes = array_merge($defaultAttributes, [
            'src' => $defaultSrc,
            'alt' => $alt,
            'loading' => 'lazy',
            'decoding' => 'async',
        ], $attributes);
        
        // Add desktop srcset if available
        if (!empty($desktopSrcset)) {
            $imgAttributes['srcset'] = implode(', ', $desktopSrcset);
        }
        
        // Add dimensions from desktop image
        $metadata = wp_get_attachment_metadata($desktopImageId);
        if (isset($metadata['width']) && isset($metadata['height'])) {
            if (!isset($imgAttributes['width'])) {
                $imgAttributes['width'] = $metadata['width'];
            }
            if (!isset($imgAttributes['height'])) {
                $imgAttributes['height'] = $metadata['height'];
            }
        }
        
        $html .= $this->buildImgTag($imgAttributes);
        $html .= '</picture>';
        
        return $html;
    }
    
    /**
     * Build srcset array for a specific image and widths
     * 
     * @param int|null $imageId
     * @param array $widths
     * @param array $glideParams Additional Glide parameters
     * @return array
     */
    protected function buildSrcsetForImage(?int $imageId, array $widths, array $glideParams = []): array
    {
        // Return empty array if imageId is null
        if ($imageId === null) {
            return [];
        }
        
        $originalUrl = wp_get_attachment_url($imageId);
        if (!$originalUrl) {
            return [];
        }
        
        // If SVG, return simple entry
        if (pathinfo($originalUrl, PATHINFO_EXTENSION) === 'svg') {
            return [$originalUrl];
        }
        
        $srcsetEntries = [];
        $metadata = wp_get_attachment_metadata($imageId);
        $maxWidth = $metadata['width'] ?? 3000;
        
        foreach ($widths as $width) {
            if ($width > $maxWidth) {
                continue;
            }
            
            // Merge width with custom Glide parameters
            $params = array_merge($glideParams, ['w' => $width]);
            
            $url = $this->images->getUrl($imageId, $params);
            $srcsetEntries[] = $url . ' ' . $width . 'w';
            
            $webpParams = array_merge($params, ['fm' => 'webp']);
            $webpUrl = $this->images->getUrl($imageId, $webpParams);
            $srcsetEntries[] = $webpUrl . ' ' . $width . 'w';
        }
        
        return $srcsetEntries;
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
    
    /**
     * Helper method to build a picture tag from attributes
     * 
     * @param array $attributes
     * @return string
     */
    protected function buildPictureTag(array $attributes): string
    {
        $html = '<picture';
        foreach ($attributes as $attr => $value) {
            if ($value !== null && $value !== '') {
                $html .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
            }
        }
        $html .= '>';
        
        return $html;
    }
    
    /**
     * Derive widths array from Glide parameters if not explicitly provided
     * 
     * @param array $widths Explicitly provided widths
     * @param array $glideParams Glide parameters that may contain width
     * @return array Final widths to use
     */
    protected function deriveWidthsFromParams(array $widths, array $glideParams): array
    {
        // If widths are explicitly provided, use them
        if (!empty($widths)) {
            return $widths;
        }
        
        // If Glide params contain a width, use that as the base
        if (isset($glideParams['w'])) {
            $baseWidth = $glideParams['w'];
            
            // Generate responsive sizes based on the base width
            return [
                (int)($baseWidth * 0.5),  // 50% for very small screens
                $baseWidth,               // Base width
                (int)($baseWidth * 1.5),  // 150% for high-DPR
                (int)($baseWidth * 2)     // 200% for very high-DPR
            ];
        }
        
        // Default fallback widths
        return [375, 750, 1100, 1500, 2200];
    }
}