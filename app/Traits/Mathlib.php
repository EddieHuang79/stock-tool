<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;

trait Mathlib
{

	//  相除的處理

	protected function except( $child, $parent )
	{

		return $parent > 0 ? $child / $parent : 0 ;

	}

}


