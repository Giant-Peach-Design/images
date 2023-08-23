<?php

namespace Giantpeach\Schnapps\Images\Facades;

class Images
{
  public static function __callStatic($name, $arguments)
  {
    return call_user_func_array([Images::getInstance(), $name], $arguments);
  }
}
