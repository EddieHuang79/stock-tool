<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class SellBuyPercent
{

    protected $table = 'stock_data';
    protected $rel_table = 'sell_buy_percent';


	public static function add_stock_data( $data )
	{

		$_this = new self;

		$result = DB::table($_this->table)->insert($data);

		return $result;

	}

	public static function add_sell_buy_percent_data( $data )
	{

		$_this = new self;

		$result = DB::table($_this->rel_table)->insert($data);

		return $result;

	}

	public static function edit_sell_buy_percent_data( $data, $id )
	{

		$_this = new self;

		$result = DB::table($_this->rel_table)->where('id', $id)->update($data);

		return $result;

	}

	public static function get_stock_list( )
	{

		$_this = new self;

		$result = DB::table($_this->table)->get();

		return $result;

	}

	public static function get_statistics( $code )
	{

		$_this = new self;

		$result = DB::table($_this->rel_table)
					->leftJoin($_this->table, $_this->rel_table.'.stock_data_id', '=', $_this->table.'.id')
					->select(
						$_this->rel_table.'.*'
					)
					->where( $_this->table . '.code', $code )
					->orderBy( $_this->rel_table.'.data_date' )
					->get();

		return $result;

	}

	public static function get_first_data_time()
	{

		$_this = new self;

		$result = DB::table($_this->rel_table)
					->select(
						$_this->rel_table.'.stock_data_id',
						DB::raw('min(data_date) as data_date')
					)
					->groupBy( $_this->rel_table.'.stock_data_id' )
					->get();

		return $result;

	}

	public static function get_last_update_time()
	{

		$_this = new self;

		$result = DB::table($_this->rel_table)
					->select(
						$_this->rel_table.'.stock_data_id',
						DB::raw('max(data_date) as data_date')
					)
					->groupBy( $_this->rel_table.'.stock_data_id' )
					->get();

		return $result;

	}

}
