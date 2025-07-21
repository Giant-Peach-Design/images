<?php

namespace Giantpeach\Schnapps\Images\Compatibility\Facades;

use Giantpeach\Schnapps\Images\Compatibility\Images as CompatibilityImagesInstance;

/**
 * Compatibility facade for Images class
 * 
 * Provides static access to the compatibility Images class methods
 * 
 * @deprecated version 3.0.0 Use Giantpeach\Schnapps\Images\Facades\Images instead
 */
class Images
{
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([CompatibilityImagesInstance::getInstance(), $name], $arguments);
    }
}