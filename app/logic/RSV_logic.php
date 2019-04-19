<?php

namespace App\logic;

use App\Traits\SchemaFunc;
use App\Traits\Mathlib;

/*

	@	https://www.moneydj.com/KMDJ/wiki/wikiViewer.aspx?keyid=02e9d1fa-499f-4952-9f5b-e7cad942c97b

	#	RSV: 未成熟隨機值( Raw Stochastic Value，簡寫為RSV)

	#	說明	

	計算RSV，提供KD值使用，為威廉指標的倒數

	#	公式

                 第N天收盤價 － 最近N日內最低價 

RSV = ---------------------------------------------------- x 100

             最近N日內最高價 － 最近N日內最低價

RSV等於0時，表示當天收盤價是9天內最低價，
RSV等於100時，當天收盤價是9天內最高價。
RSV等於50，則表示收盤價在高低價的中間。

*/


class RSV_logic extends Basetool
{

	use SchemaFunc, Mathlib;

	protected $n = 9;

	// 		計算資料

	public static function count_data( $code )
	{

		$_this = new self();

		$result = false;

		if ( !empty($code) ) 
		{

			$n = $_this->n;

			$stock_data = Stock_logic::get_stock( $code );

			$data = Stock_logic::get_stock_data( $stock_data->id )->toArray();

			$stock_data_id = $_this->pluck( $data, "id" );

			$exist_data = TechnicalAnalysis_logic::get_data( 1, $stock_data_id );

			$exist_array = $_this->pluck( $exist_data, "stock_data_id" );

			foreach ($data as $key => $row) 
			{

				if ( $key < $n - 1 || in_array($row->id, $exist_array) ) 
				{
					
					continue;
					
				}

				// 找出N天內最高價

				$highest_close_value = $_this->get_highest_close_value( $data, $key );

				// 找出N天內最低價

				$lowest_close_value = $_this->get_lowest_close_value( $data, $key );

				// 收盤價

				$close = floatval($row->close);

				// RSV

				$result = $_this->except( $close - $lowest_close_value, $highest_close_value - $lowest_close_value ) * 100;

				// 寫入資料庫

				$option = [
					"stock_data_id" => $row->id,
					"value" 		=> round($result, 2)
				];

				// 寫個防止重複

				$insert_data = TechnicalAnalysis_logic::insert_format( $option, 1 );

				TechnicalAnalysis_logic::add_data( $insert_data );

			}

		}

		return $result;

	}


	// 	找出N天內最高價

	protected function get_highest_close_value( $data, $key )
	{

		$_this = new self();

		$result = '';

		if ( !empty($data) && is_array($data) && is_int($key) ) 
		{

			$n = $_this->n;

			$start = $key - ($n - 1);

			if ( $start >= 0 ) 
			{

				$sub_data = array_slice( $data, $start, $n );

				$result = collect( $sub_data )->pluck( "highest" )->max();

				$result = floatval($result);

			}
			
		}

		return $result;

	}


	// 	找出N天內最低價

	protected function get_lowest_close_value( $data, $key )
	{

		$_this = new self();

		$result = '';

		if ( !empty($data) && is_array($data) && is_int($key) ) 
		{

			$n = $_this->n;

			$start = $key - ($n - 1);

			if ( $start >= 0 ) 
			{

				$sub_data = array_slice( $data, $start, $n );

				$result = collect( $sub_data )->pluck( "lowest" )->min();

				$result = floatval($result);

			}
			
		}

		return $result;

	}


	// 	get rsv data

	public static function get_rsv_data( $code )
	{

		$_this = new self();

		$result = [];

		if ( !empty($code) ) 
		{

			$stock_data = Stock_logic::get_stock( $code );

			$data = Stock_logic::get_stock_data( $stock_data->id )->toArray();		

			$stock_data_id = $_this->pluck( $data, "id" );

			$exist_data = TechnicalAnalysis_logic::get_data( 1, $stock_data_id );

			$result = collect( $exist_data )->map( function( $value, $key ) use( $result ) { 
				return [
					"stock_data_id"	=> $value->stock_data_id,
					"data_date"	 	=> $value->data_date,
					"value" 		=> floatval($value->value)
				]; 
			} )->toArray();

		}

		return $result;

	}

}
