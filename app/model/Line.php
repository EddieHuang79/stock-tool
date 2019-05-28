<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Line
{

    private $table = 'line';

	public function add_data( $data )
	{

		$user_id = DB::table($this->table)->insertGetId($data);

		return $user_id;

	}

	public function get_data()
	{

		$user_id = DB::table($this->table)->get();

		return $user_id;

	}

    public static function getInstance()
    {

        return new self;

    }

}
