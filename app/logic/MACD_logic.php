<?php

namespace App\logic;

use App\Traits\SchemaFunc;
use App\Traits\Mathlib;

/*
	
	@	http://nengfang.blogspot.com/2014/09/macd-excel.html

	#	公式

	DI = (最高價 + 最低價 + 2 × 收盤價) ÷ 4

	首日EMA12 = 12天內DI 總和 ÷ 12 
	首日EMA26 = 26天內DI 總和 ÷ 26 

	EMA12 = [前一日EMA12 × (12 - 1) + 今日DI × 2] ÷ (12+1)
	EMA26 = [前一日EMA26 × (26 - 1) + 今日DI × 2] ÷ (26+1)

	DIF = 12日EMA - 26日EMA 

	首日MACD = 9天內DIF總和 ÷ 9 
	MACD = (前一日MACD × 8/10 + 今日DIF × 2/10 

	OSC = DIF - MACD 

*/

class MACD_logic extends Basetool
{

	use SchemaFunc, Mathlib;

	// 		計算資料

	public static function count_data( $code )
	{

		$_this = new self();

		$result = false;

		if ( !empty($code) ) 
		{

			$stock_data = Stock_logic::get_stock( $code );

			$data = Stock_logic::get_stock_data( $stock_data->id )->toArray();

			$stock_data_id = $_this->pluck( $data, "id" );

			$exist_array = [
				"DIFF" 			=> $_this->pluck( TechnicalAnalysis_logic::get_data( 6, $stock_data_id ), "stock_data_id" ),
				"MACD" 			=> $_this->pluck( TechnicalAnalysis_logic::get_data( 7, $stock_data_id ), "stock_data_id" ),
				"DIFF-MACD" 	=> $_this->pluck( TechnicalAnalysis_logic::get_data( 8, $stock_data_id ), "stock_data_id" )
			];

			$n1 = 12;

			$n2 = 26;

			$n3 = 9;

			$MACD = [
				"DI" 				=> [],
				"EMA12" 			=> [],
				"EMA26" 			=> [],
				"DIFF" 				=> [],
				"MACD" 				=> [],
				"DIFF-MACD" 		=> []
			];

			$tmp = [];

			foreach ($data as $key => $row) 
			{

				// DI

				$MACD["DI"][$key] = $_this->except( floatval($row->highest) + floatval($row->lowest) + floatval($row->close) * 2, 4 );

				// EMA12 = 前一日EMA12 × (11/13) + 今日DI × 2/13 

				if ( $key >= $n1 - 1 ) 
				{

					$MACD["EMA12"][$key] = $_this->get_Wilders_value( $MACD["EMA12"], $MACD["DI"], $key, $n1 );

				}

				// EMA26 = 前一日EMA26 × (25/27) + 今日DI × 2/27 

				if ( $key >= $n2 - 1 ) 
				{

					$MACD["EMA26"][$key] = $_this->get_Wilders_value( $MACD["EMA26"], $MACD["DI"], $key, $n2 );

				}

				// DIFF = 12日EMA - 26日EMA 

				if ( isset($MACD["EMA26"][$key]) ) 
				{
					
					$MACD["DIFF"][$key] = $MACD["EMA12"][$key] - $MACD["EMA26"][$key];
					
				}

				/*

					MACD = (前一日MACD × 8/10 + 今日DIF × 2/10 

				*/

				if ( isset($MACD["DIFF"][$key-$n3-1]) ) 
				{

					$sub_data = array_slice( $MACD["DIFF"], $key-$n3-1, $n3 );

					$MACD["MACD"][$key] = isset($MACD["MACD"][$key - 1]) ? $MACD["MACD"][$key - 1] * 0.8 + $MACD["DIFF"][$key] * 0.2 : $_this->except( array_sum( $sub_data ), $n3 ) ;
					
				}

				// DIFF - MACD

				if ( isset($MACD["DIFF"][$key]) && isset($MACD["MACD"][$key]) ) 
				{

					$MACD["DIFF-MACD"][$key] = $MACD["DIFF"][$key] - $MACD["MACD"][$key] ;
					
				}

				// 寫入DB

				if ( isset($MACD["DIFF"][$key]) && !in_array($row->id, $exist_array["DIFF"]) ) 
				{

					$option = [
						"stock_data_id" => $row->id,
						"value" 		=> round($MACD["DIFF"][$key], 2)
					];

					$insert_data = TechnicalAnalysis_logic::insert_format( $option, 6 );

					TechnicalAnalysis_logic::add_data( $insert_data );

				}

				if ( isset($MACD["MACD"][$key]) && !in_array($row->id, $exist_array["MACD"]) ) 
				{

					$option = [
						"stock_data_id" => $row->id,
						"value" 		=> round($MACD["MACD"][$key], 2)
					];

					$insert_data = TechnicalAnalysis_logic::insert_format( $option, 7 );

					TechnicalAnalysis_logic::add_data( $insert_data );

				}

				if ( isset($MACD["DIFF-MACD"][$key]) && !in_array($row->id, $exist_array["DIFF-MACD"]) ) 
				{

					$option = [
						"stock_data_id" => $row->id,
						"value" 		=> round($MACD["DIFF-MACD"][$key], 2)
					];

					$insert_data = TechnicalAnalysis_logic::insert_format( $option, 8 );

					TechnicalAnalysis_logic::add_data( $insert_data );

				}

			}

		}

		return $result;

	}


	// 	威爾德平滑法

	protected function get_Wilders_value( $main, $sub, $key, $n )
	{

		$_this = new self();

		$result = 0;

		if ( is_array($main) && !empty($sub) && is_array($sub) && is_int($key) && !empty($n) && is_int($n) ) 
		{

			$start = $key - ($n - 1);

			$sub_data = array_slice( $sub, $start, $n );

			$result = isset($main[$key - 1]) ? $main[$key - 1] * $_this->except( $n - 1, $n + 1 ) + $sub[$key] * $_this->except( 2, $n + 1 ) : $_this->except( array_sum( $sub_data ), $n ) ;

			// if ( isset($main[$key - 1]) ) 
			// {
				
			// 	// EMA12 = [前一日EMA12 × (12 - 1) + 今日DI × 2] ÷ (12+1)

			// 	echo "n: " . $n . '<br/>';
			// 	echo "前一日EMA12: " . $main[$key - 1] . '<br/>';
			// 	echo "今日DI: " . $sub[$key] . '<br/>';
			// 	echo "前一日EMA12 × (11/13) : " . $main[$key - 1] * $_this->except( $n - 1, $n + 1 ) . '<br/>';
			// 	echo "今日DI × 2/13  : " . $sub[$key] * $_this->except( 2, $n + 1 ) . '<br/>';
			// 	echo "result: " . $result . '<br/>';


			// 	dd($sub_data);
				
			// }

		}

		return $result;

	}

}
