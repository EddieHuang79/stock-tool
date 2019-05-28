<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Holiday
{

	private $table = 'holiday';

	public function get_list()
	{

		$result = DB::table($this->table)->select("holiday_date")->get();

		return $result;

	}

	public function add_data( $data )
	{

		$result = DB::table($this->table)->insert($data);

		return $result;

	}

    public static function getInstance()
    {

        return new self;

    }

}
