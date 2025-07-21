# Images - Simple WordPress Image Processing

A streamlined image processing package for WordPress using Glide. Focuses on core functionality: converting image IDs to optimized URLs and creating responsive image tags.

## Installation

```bash
composer require giantpeach/images
```

## Core Features

- **Simple API**: Get optimized image URLs from WordPress attachment IDs
- **Glide Integration**: Automatic image resizing, cropping, and format conversion
- **Responsive Images**: Generate `<img>` tags with srcset for performance
- **Art Direction**: `<picture>` elements for different images per viewport
- **WebP Support**: Automatic WebP variant generation
- **SVG Handling**: Passes through SVG files without processing

## Basic Usage

### Get Image URL

```php
// Simple image URL with width
$url = gp_image_url($imageId, ['w' => 800]);

// With height and crop
$url = gp_image_url($imageId, ['w' => 800, 'h' => 600, 'fit' => 'crop']);

// WebP format
$url = gp_image_url($imageId, ['w' => 800, 'fm' => 'webp']);

// Via facade
use Giantpeach\Schnapps\Images\Facades\Images;
$url = Images::getUrl($imageId, ['w' => 800]);
```

### Responsive Image Tags

```php
// Simple responsive image
echo gp_image_tag($imageId);

// With custom sizes
echo gp_image_tag($imageId, '(max-width: 768px) 100vw, 50vw');

// With custom widths and attributes
echo gp_image_tag($imageId, '100vw', [400, 800, 1200], ['class' => 'hero']);
```

### Picture Tags (Art Direction)

```php
// Different images for mobile vs desktop
echo gp_picture_tag($mobileImageId, $desktopImageId);

// Custom breakpoint
echo gp_picture_tag($mobileImageId, $desktopImageId, '768px');

// Full control
echo gp_picture_tag(
    $mobileImageId, 
    $desktopImageId, 
    '640px',                    // breakpoint
    [375, 750],                 // mobile widths
    [1100, 1500, 2200],         // desktop widths
    ['class' => 'art-directed'] // attributes
);
```

## Glide Parameters

All standard Glide parameters are supported:

- `w` - Width
- `h` - Height  
- `fit` - Resize fit (`crop`, `contain`, `fill`, `stretch`, `crop-center`)
- `fm` - Format (`jpg`, `png`, `webp`, `gif`)
- `q` - Quality (1-100)
- `blur` - Blur amount
- `pixel` - Pixelate amount
- `filt` - Filters (`greyscale`, `sepia`)

## Configuration

```php
// Optional: Configure Glide server options
use Giantpeach\Schnapps\Images\Facades\Images;
Images::config([
    'driver' => 'imagick', // or 'gd'
    'max_image_size' => 2000*2000,
]);
```

## URL Structure

Images are served from `/img/` path:
- Original: `/wp-content/uploads/image.jpg`
- Processed: `/img/image.jpg?w=800&h=600&fit=crop`

## Server Configuration

### Nginx
```nginx
location ~ \/img/(.+\.(png|jpg|jpeg|gif|webp))$ {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

### Apache
Add to your `.htaccess`:
```apache
RewriteRule ^img/(.+\.(png|jpg|jpeg|gif|webp))$ index.php [QSA,L]
```

## Migration from v1.x

**Breaking Changes:**
- Removed config-based image sizes
- Removed multi-viewport image arrays  
- Simplified API to focus on core functionality
- `getGlideImageUrl()` renamed to `getUrl()`
- Removed deprecated methods

**Migration:**
```php
// Old
$url = Images::getInstance()->getGlideImageUrl($id, $params);

// New (using facade)
use Giantpeach\Schnapps\Images\Facades\Images;
$url = Images::getUrl($id, $params);

// Or use helper
$url = gp_image_url($id, $params);
```

## Requirements

- PHP 8.0+
- WordPress
- League/Glide ^2.2