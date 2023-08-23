<?php

namespace Giantpeach\Schnapps\Images\Facades;

use Giantpeach\Schnapps\Images\Images as ImagesInstance;

class Images
{
  public static function __callStatic($name, $arguments)
  {
    return call_user_func_array([ImagesInstance::getInstance(), $name], $arguments);
  }
}
