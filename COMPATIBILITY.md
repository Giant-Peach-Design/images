# Images Package v3.0 Compatibility Guide

The Images package v3.0 introduced breaking changes to simplify the API and remove complex configuration-based features. To help with migration, we've introduced a compatibility layer that preserves the old API.

## Using the Compatibility Layer

### Facade Usage (Recommended)

```php
use Giantpeach\Schnapps\Images\Compatibility\Facades\Images;

// All methods available as static calls
$url = Images::getGlideImageUrl($imageId, ['w' => 500, 'h' => 300]);
$imageSet = Images::get($imageId, 'medium', $mobileImageId, $tabletImageId);
$url = Images::getImageUrlForSize($imageId, 'hero.desktop');
```

### Class-based Usage

```php
use Giantpeach\Schnapps\Images\Compatibility\Images as CompatibilityImages;

$images = CompatibilityImages::getInstance();

// Old method names still work
$url = $images->getGlideImageUrl($imageId, ['w' => 500, 'h' => 300]);

// Config-based sizes (with default sizes provided)
$imageSet = $images->get($imageId, 'medium', $mobileImageId, $tabletImageId);
// Returns: ['desktop' => '...', 'mobile' => '...', 'tablet' => '...']

// Single image with default parameters
$url = $images->getImage($imageId, ['w' => 500, 'h' => 500, 'fit' => 'crop']);

// Multi-viewport images
$imageSet = $images->getImages(
    $desktopImageId,
    ['image' => $mobileImageId, 'params' => ['w' => 375]],
    ['image' => $tabletImageId, 'params' => ['w' => 768]]
);
```

### Helper Function Usage

```php
// Legacy helper functions (all deprecated but functional)
$imageSet = gp_get_image($imageId, 'large', $mobileImageId, $tabletImageId);
$url = gp_get_single_image($imageId, ['w' => 500, 'h' => 300]);
$url = gp_get_glide_image_url($imageId, ['w' => 500]);
$url = gp_get_image_url_for_size($imageId, 'medium');
```

## Default Image Sizes

The compatibility layer includes these default image sizes:

- `thumbnail`: Desktop 300x300, Mobile 150x150, Tablet 225x225
- `medium`: Desktop 600x400, Mobile 300x200, Tablet 450x300
- `large`: Desktop 1200x800, Mobile 600x400, Tablet 900x600
- `hero`: Desktop 1920x1080, Mobile 768x432, Tablet 1024x576

## Configuration

### Config Package Integration

The compatibility layer integrates with the `giantpeach/config` package if available. Create an `image-sizes.php` config file:

```php
// config/image-sizes.php
return [
    'hero' => [
        'desktop' => ['w' => 1920, 'h' => 1080, 'fit' => 'crop'],
        'mobile' => ['w' => 768, 'h' => 432, 'fit' => 'crop'],
        'tablet' => ['w' => 1024, 'h' => 576, 'fit' => 'crop']
    ],
    'card' => [
        'desktop' => ['w' => 400, 'h' => 300, 'fit' => 'crop'],
        'mobile' => ['w' => 300, 'h' => 225, 'fit' => 'crop'],
        'tablet' => ['w' => 350, 'h' => 262, 'fit' => 'crop']
    ]
];
```

### Nested Size Keys

You can use dotted notation for specific viewport sizes:

```php
// Get specific viewport size from config
$url = Images::getImageUrlForSize($imageId, 'hero.desktop'); // Uses hero.desktop config
$url = Images::getImageUrlForSize($imageId, 'hero.mobile');  // Uses hero.mobile config

// Or get all viewports
$imageSet = Images::get($imageId, 'hero'); // Returns desktop, mobile, tablet URLs
```

### Manual Configuration

You can also configure custom image sizes programmatically:

```php
$images = CompatibilityImages::getInstance();
$images->config([
    'image-sizes' => [
        'custom-size' => [
            'desktop' => ['w' => 800, 'h' => 600, 'fit' => 'crop'],
            'mobile' => ['w' => 400, 'h' => 300, 'fit' => 'crop'],
            'tablet' => ['w' => 600, 'h' => 450, 'fit' => 'crop']
        ]
    ]
]);
```

## Migration Path

**⚠️ All compatibility methods are deprecated and will be removed in an upcoming verison**

### Instead of:

```php
$images->getGlideImageUrl($imageId, ['w' => 500]);
```

### Use:

```php
$images->getUrl($imageId, ['w' => 500]);
// or helper: gp_image_url($imageId, ['w' => 500])
```

### Instead of config-based sizes:

```php
$imageSet = $images->get($imageId, 'medium');
```

### Use explicit parameters:

```php
$desktopUrl = $images->getUrl($imageId, ['w' => 600, 'h' => 400, 'fit' => 'crop']);
$mobileUrl = $images->getUrl($imageId, ['w' => 300, 'h' => 200, 'fit' => 'crop']);
```

### For responsive images:

```php
// Instead of manual multi-viewport handling
// Use the new responsive image methods:
$tag = $images->createImageTag($imageId, '(min-width: 768px) 50vw, 100vw');
// or helper: gp_image_tag($imageId, '(min-width: 768px) 50vw, 100vw')
```

## Deprecated Features

- ❌ `getGlideImageUrl()` → Use `getUrl()`
- ❌ `get()` with config sizes → Use `getUrl()` with explicit params
- ❌ `getImage()` → Use `getUrl()`
- ❌ `getImages()` → Use `getUrl()` for each viewport
- ❌ `getImageUrlForSize()` → Use `getUrl()` with explicit params
- ❌ Config-based image sizes → Use explicit parameters
- ❌ All `gp_get_*` helper functions → Use `gp_image_url()`

The compatibility layer ensures your existing code continues to work while you migrate to the new API.
