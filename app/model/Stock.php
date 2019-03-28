<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Stock
{

    protected $table = 'stock_data';

	public static function get_list( )
	{

		$_this = new self;

		$result = DB::table($_this->table)->get();

		return $result;

	}

	public static function get_stock( $code )
	{

		$_this = new self;

		$result = DB::table($_this->table)->where( 'code', $code )->first();

		return $result;

	}

}
