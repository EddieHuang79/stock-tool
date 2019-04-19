<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Line
{

	protected $table = 'line';

	public static function add_data( $data )
	{

		$_this = new self;

		$user_id = DB::table($_this->table)->insertGetId($data);

		return $user_id;

	}

	public static function get_data()
	{

		$_this = new self;

		$user_id = DB::table($_this->table)->get();

		return $user_id;

	}

}
