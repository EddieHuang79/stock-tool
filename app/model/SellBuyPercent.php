<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class SellBuyPercent
{

    protected $stock_info_table = 'stock_info';
    protected $stock_data_table = 'stock_data';
    protected $table = 'sell_buy_percent';

	public static function add_sell_buy_percent_data( $data )
	{

		$_this = new self;

		$result = DB::table($_this->table)->insert($data);

		return $result;

	}

	public static function edit_sell_buy_percent_data( $data, $id )
	{

		$_this = new self;

		$result = DB::table($_this->table)->where('id', $id)->update($data);

		return $result;

	}

	public static function get_statistics( $stock_id )
	{

		$_this = new self;

		$result = DB::table($_this->stock_data_table)
					->leftJoin($_this->table, $_this->table.'.stock_data_id', '=', $_this->stock_data_table.'.id')
					->select(
						$_this->stock_data_table.'.id as data_id',
						$_this->stock_data_table.'.data_date',
						$_this->stock_data_table.'.volume',
						$_this->stock_data_table.'.open',
						$_this->stock_data_table.'.highest',
						$_this->stock_data_table.'.lowest',
						$_this->stock_data_table.'.close',
						$_this->table.'.id',
						$_this->table.'.spread',
						$_this->table.'.buy1',
						$_this->table.'.sell1',
						$_this->table.'.buy2',
						$_this->table.'.sell2',
						$_this->table.'.rally_total',
						$_this->table.'.tumbled_total',
						$_this->table.'.rally_num1',
						$_this->table.'.tumbled_num1',
						$_this->table.'.rally_total_20days',
						$_this->table.'.tumbled_total_20days',
						$_this->table.'.result'
					)
					->where( $_this->stock_data_table . '.stock_id', $stock_id )
					->orderBy( $_this->stock_data_table.'.data_date' )
					->get();

		return $result;

	}

	public static function get_first_data_time()
	{

		$_this = new self;

		$result = DB::table($_this->stock_data_table)
					->select(
						$_this->stock_data_table.'.stock_id',
						DB::raw('min(data_date) as data_date')
					)
					->groupBy( $_this->stock_data_table.'.stock_id' )
					->get();

		return $result;

	}

	public static function get_last_update_time()
	{

		$_this = new self;

		$result = DB::table($_this->stock_data_table)
					->select(
						$_this->stock_data_table.'.stock_id',
						DB::raw('max(data_date) as data_date')
					)
					->groupBy( $_this->stock_data_table.'.stock_id' )
					->get();

		return $result;

	}

	public static function get_all_statistics_status()
	{

		$_this = new self;

		$result = DB::table($_this->stock_data_table)
					->leftJoin($_this->stock_info_table, $_this->stock_info_table.'.id', '=', $_this->stock_data_table.'.stock_id')
					->leftJoin($_this->table, $_this->table.'.stock_data_id', '=', $_this->stock_data_table.'.id')
					->select(
						$_this->stock_info_table.'.code',
						$_this->stock_data_table.'.data_date',
						$_this->table.'.result'
					)
					->orderBy( $_this->stock_data_table.'.data_date' )
					->get();

		return $result;

	}

}
