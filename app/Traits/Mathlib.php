<?php

namespace App\Traits;

trait Mathlib
{
    //  相除的處理

    protected function except($child, $parent)
    {
        return $parent > 0 ? $child / $parent : 0;
    }
}
