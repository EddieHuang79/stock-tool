<?php

namespace App\logic;

use App\model\TechnicalAnalysis;
use App\Traits\SchemaFunc;
use App\Traits\Mathlib;

class TechnicalAnalysis_logic extends Basetool
{

	use SchemaFunc, Mathlib;

	// type - 1: RSV, 2: K, 3: D, 4: RSI5, 5: RSI10, 6: DIFF, 7: MACD, 8: OSC

	public static function insert_format( $data, $type = 1 )
	{

		$result = array();

		if ( !empty($data) && is_array($data) && !empty($type) && is_int($type) ) 
		{

			$result = array(
				"stock_data_id"       	=> isset($data["stock_data_id"]) ? intval($data["stock_data_id"]) : "",
				"type"       			=> $type,
				"value"       			=> isset($data["value"]) ? floatval($data["value"]) : "",
				"created_at"    		=> date("Y-m-d H:i:s"),
				"updated_at"    		=> date("Y-m-d H:i:s")
			);

		}

		return $result;

	}


	// 		寫入資料

	public static function add_data( $data )
	{

		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{

			$result = TechnicalAnalysis::add_data( $data );

		}

		return $result ;

	}


	// 		取得資料

	public static function get_data( $type, $id = [] )
	{

		$_this = new self();

		$result = [];

		if ( !empty($type) && is_int($type) ) 
		{

			$result = TechnicalAnalysis::get_data( $type, $id );

		}

		return $result ;

	}



	// 計算交錯信號

	/*
		
		code: 股票代號
		type: 1: KD, 2: RSI, 3: MACD
		start: 偵測開始區間
		end: 偵測結束區間

	*/

	public static function get_cross_sign( $type, $start, $end )
	{

		$_this = new self();

		$result = [];

		if ( !empty($type) && is_int($type) && !empty($start) && is_string($start) && !empty($end) && is_string($end) ) 
		{

			$option = [
				"type" 	=> [],
				"start" => $start,
				"end" 	=> $end
			];

			switch ($type) 
			{

				// KD

				case 1:
					
					$option["type"] = [2, 3];

					$key1 = 2;

					$key2 = 3;

					break;

				// RSI
				
				case 2:
					
					$option["type"] = [4, 5];

					$key1 = 4;

					$key2 = 5;

					break;

				// MACD

				case 3:

					$option["type"] = [6, 7];

					$key1 = 6;

					$key2 = 7;

					break;

			}		
			

			$data = $_this->count_cross_data( $option );

			$tmp = [];

			foreach ($data as $row) 
			{

				$tmp[$row->code][$row->type][$row->data_date] = $row->value; 

			}

			// status: 若是A值比B值大，給1，反之給2

			foreach ($tmp as $code => $item) 
			{

				$status = floatval( current($item[$key1]) ) > floatval( current($item[$key2]) ) ? 1 : 2 ;

				$cross_sign = [
					"gold_cross" => [],
					"dead_cross" => []
				];

				foreach ($item[$key1] as $date => $row) 
				{

					switch ( $status ) 
					{

						// 初始值A > B，因此當出現B > A時回報死叉

						case 1:
								
							if ( floatval( $row ) < floatval( $item[$key2][$date] ) ) 
							{
								
								$cross_sign["dead_cross"][] = [
									"date" 		=> $date,
									"value1" 	=> floatval( $row ),
									"value2" 	=> floatval( $item[$key2][$date] )
								];
								
								$status = 2;

							}

							break;

						// 初始值B > A，因此當出現B > A時回報金叉
						
						case 2:

							if ( floatval( $row ) > floatval( $item[$key2][$date] ) ) 
							{
								
								$cross_sign["gold_cross"][] = [
									"date" 		=> $date,
									"value1" 	=> floatval( $row ),
									"value2" 	=> floatval( $item[$key2][$date] )
								];

								$status = 1;
								
							}

							break;
					
					}

				}

				$result[$code] = $cross_sign;				

			}

		}

		return $result;

	}


	// 取得資料 > for指標交叉使用

	protected function count_cross_data( $option )
	{

		$result = [];

		if ( !empty($option) && is_array($option) ) 
		{

			$result = TechnicalAnalysis::count_cross_data( $option );
			
		}

		return $result;

	}


	// 取得通知範圍

	/*

		距離今日在5個工作日內(懶得判斷假日)
		金叉通知: value1 & value2 < 20才通知, type: 1
		死叉通知: value1 & value2 > 80才通知, type: 2

	*/

	public static function is_notice_data( $data )
	{

		$result = [
			"type" 		=> 1,
			"status" 	=> false,
			"data"		=> []
		];

		$diff_limit = 86400 * 5;

		foreach ($data["gold_cross"] as $row) 
		{

			if ( $row["value1"] <= 20 && $row["value2"] <= 20 && time() - strtotime($row["date"]) <= $diff_limit ) 
			{
				
				$result = [
					"type" 		=> 1,
					"status" 	=> true,
					"data"		=> $row
				];
				
			}

		}

		foreach ($data["dead_cross"] as $row) 
		{

			if ( $row["value1"] >= 80 && $row["value2"] >= 80 && time() - strtotime($row["date"]) <= $diff_limit ) 
			{
				
				$result = [
					"type" 		=> 2,
					"status" 	=> true,
					"data"		=> $row
				];
				
			}

		}

		return $result;

	}


	// 		取得資料

	public static function get_count_data( $type )
	{

		$_this = new self();

		$result = [];

		if ( !empty($type) && is_int($type) ) 
		{

			$result = TechnicalAnalysis::get_count_data( $type );

		}

		return $result ;

	}


	// 		上引線

	public static function hasUpperShadows( $data )
	{

		$_this = new self();

		$result = [
			"status" 	=> false,
			"data"		=> ''
		];

		if ( !empty($data) && is_object($data) ) 
		{

			$parent = floatval($data->close) > floatval($data->open) ? floatval($data->close) : floatval($data->open) ;

			$child = floatval($data->highest) - $parent;

			$value = $_this->except( $child, $parent ) ;

			$result = [
				"status" 	=> $value > 0.05,
				"data"		=> $value
			];

		}

		return $result;

	}


	// 		下引線

	public static function hasLowerShadows( $data )
	{

		$_this = new self();

		$result = [
			"status" 	=> false,
			"data"		=> ''
		];

		if ( !empty($data) && is_object($data) ) 
		{


			$parent = floatval($data->close) > floatval($data->open) ? floatval($data->close) : floatval($data->open) ;

			$child = $parent - floatval($data->lowest);

			$value = $_this->except( $child, $parent ) ;

			$result = [
				"status" 	=> $value > 0.03,
				"data"		=> $value
			];
			
		}

		return $result;

	}


}







