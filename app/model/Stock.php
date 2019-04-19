<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Stock
{

    protected $table = 'stock_info';
    protected $data_table = 'stock_data';

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


	public static function get_stock_list( )
	{

		$_this = new self;

		$result = DB::table($_this->table)->get();

		return $result;

	}

	// migrate時寫入了

	// public static function add_stock_info( $data )
	// {

	// 	$_this = new self;

	// 	$result = DB::table($_this->table)->insert($data);

	// 	return $result;

	// }

	public static function add_stock_data( $data )
	{

		$_this = new self;

		$result = DB::table($_this->data_table)->insert($data);

		return $result;

	}

	public static function get_stock_data( $stock_id )
	{

		$_this = new self;

		$result = DB::table($_this->data_table)->where( 'stock_id', $stock_id )->orderBy( $_this->data_table . '.data_date' )->get();

		return $result;

	}

	public static function get_all_stock_data( $type = 1 )
	{

		$_this = new self;

		$result = DB::table($_this->data_table)
				->leftJoin($_this->table, $_this->table.'.id', '=', $_this->data_table.'.stock_id')
				->select(
					$_this->table . '.code',
					$_this->data_table . '.data_date'
				);
		$result = $type === 2 ? $result->whereBetween( $_this->data_table . '.data_date', [ date("Y-m-01"), date("Y-m-t") ] ) : $result ;
		$result = $result->groupBy( $_this->data_table . '.data_date', $_this->table.'.code' )
				->orderBy( $_this->data_table . '.data_date' )
				->get();

		return $result;

	}


	public static function get_all_stock_info()
	{

		$_this = new self;

		$result = DB::table($_this->table)->get();

		return $result;

	}


	public static function get_all_stock_data_id()
	{

		$_this = new self;

		$result = DB::table($_this->data_table)
				->leftJoin($_this->table, $_this->table.'.id', '=', $_this->data_table.'.stock_id')
				->select(
					$_this->table . '.code',
					$_this->data_table . '.id as stock_data_id'
				)
				->groupBy( $_this->data_table . '.id', $_this->table.'.code' )
				->orderBy( $_this->data_table . '.id' )
				->get();

		return $result;

	}

	public static function get_stock_data_by_date_range( $start, $end )
	{

		$_this = new self;

		$result = DB::table($_this->data_table)
				->leftJoin($_this->table, $_this->table.'.id', '=', $_this->data_table.'.stock_id')
				->select(
					$_this->table . '.code',
					$_this->data_table . '.data_date',
					$_this->data_table . '.volume',
					$_this->data_table . '.open',
					$_this->data_table . '.highest',
					$_this->data_table . '.lowest',
					$_this->data_table . '.close'
				)
				->whereBetween( $_this->data_table . '.data_date', [ $start, $end ] )
				->orderBy( $_this->data_table . '.data_date' )
				->get();

		return $result;

	}

}
