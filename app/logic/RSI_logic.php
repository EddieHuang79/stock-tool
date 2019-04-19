<?php

namespace App\logic;

use App\Traits\SchemaFunc;
use App\Traits\Mathlib;

/*

	@	https://www.ezchart.com.tw/inds.php?IND=RSI
	@	https://www.moneydj.com/KMDJ/Wiki/wikiViewer.aspx?keyid=1342fb37-760e-48b0-9f27-65674f6344c9

	#	說明	
		
		RSI 目前已為市場普遍使用，是主要技術指標之一，其主要特點是計算某一段時間內買賣雙方力量，
		作為超買、超賣的參考與Ｋ線圖及其他技術指標（三至五種）一起使用，以免過早賣及買進，造成少賺多賠的損失。

	#	計算公式	

		      過去n日內上漲點數總和
		UP＝────────────────────────
		                n
		      過去n日內下跌點數收總和
		DN＝────────────────────────
		                n
		         UP
		RS  ＝  ────
		         DN
		       　　　　　  100
		n日RSI＝100 －　────────
		                 1+RS
             
	#	使用方法	

	1.	以6日RSI值為例，80以上為超買，90以上或M頭為賣點；20以下為超賣，10以下或W底為買點。
	2.	在股價創新高點，同時RSI也創新高點時，表示後市仍強，若未創新高點為賣出訊號。
	3.	在股價創新低點，RSI也創新低點，則後市仍弱，若RSI未創新低點，則為買進訊號。
	4.	當6日RSI由下穿過12日RSI而上時，可視為買點；反之當6日RSI由上貫破12日RSI而下時，可視為賣點。
	5.	當出現類似這樣的訊號：3日RSI>5日RSI>10日RSI>20日RSI....，顯示市場是處於多頭行情；反之則為空頭行情。
	6.	盤整期中，一底比一底高，為多頭勢強，後勢可能再漲一段，是買進時機，反之一底比一底低是賣出時機。

	#	實作目標

	用5日RSI、10日RSI來計算


	#	簡化公式

	RSI= UP / ( DN + UP ) * 100

*/


class RSI_logic extends Basetool
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

			$data = Stock_logic::get_stock_data( $stock_data->id )->map( function( $item, $key ) {
				$item->volume = intval($item->volume);
				$item->open = floatval($item->open);
				$item->close = floatval($item->close);
				$item->highest = floatval($item->highest);
				$item->lowest = floatval($item->lowest);
				return $item;
			} )->toArray();

			$stock_data_id = $_this->pluck( $data, "id" );

			$exist_array = [
				"RSI5" 	=> $_this->pluck( TechnicalAnalysis_logic::get_data( 4, $stock_data_id ), "stock_data_id" ),
				"RSI10" => $_this->pluck( TechnicalAnalysis_logic::get_data( 5, $stock_data_id ), "stock_data_id" )
			];

			$n1 = 5;

			$n2 = 10;

			$RSI = [
				"5" 	=> [],
				"10" 	=> []
			];

			$rise_num = [];

			$fall_num = [];

			$UP = [
				"5" 	=> [],
				"10" 	=> []
			];

			$DN = [
				"5" 	=> [],
				"10" 	=> []		
			];

			$tmp = [];

			foreach ($data as $key => $row) 
			{

				// 上漲點數(與前日比)

				$rise_num[$key] = $_this->get_rise_num_value( $data, $key );

				// 下跌點數(與前日比)

				$fall_num[$key] = $_this->get_fall_num_value( $data, $key, $n1 );

				if ( $key >= $n1 - 1 && !in_array($row->id, $exist_array["RSI5"]) ) 
				{

					// 5日內上漲總和平滑平均值 威爾德平滑法

					$UP[$n1][$key] = $_this->get_Wilders_value( $UP[$n1], $rise_num, $key, $n1 );

					// 5日內下跌總和平滑平均值 威爾德平滑法

					$DN[$n1][$key] = $_this->get_Wilders_value( $DN[$n1], $fall_num, $key, $n1 );

					// RSI = UP / ( DN + UP ) * 100

					$RSI = $_this->except( $UP[$n1][$key], $UP[$n1][$key] + $DN[$n1][$key] ) * 100 ;

					$option = [
						"stock_data_id" => $row->id,
						"value" 		=> round($RSI, 2)
					];

					$insert_data = TechnicalAnalysis_logic::insert_format( $option, 4 );

					TechnicalAnalysis_logic::add_data( $insert_data );

				}
			
				if ( $key >= $n2 - 1 && !in_array($row->id, $exist_array["RSI10"]) ) 
				{

					// 5日內上漲總和平滑平均值 威爾德平滑法

					$UP[$n2][$key] = $_this->get_Wilders_value( $UP[$n2], $rise_num, $key, $n2 );

					// 5日內下跌總和平滑平均值 威爾德平滑法

					$DN[$n2][$key] = $_this->get_Wilders_value( $DN[$n2], $fall_num, $key, $n2 );

					// RSI = UP / ( DN + UP ) * 100

					$RSI = $_this->except( $UP[$n2][$key], $UP[$n2][$key] + $DN[$n2][$key] ) * 100 ;

					$option = [
						"stock_data_id" => $row->id,
						"value" 		=> round($RSI, 2)
					];

					$insert_data = TechnicalAnalysis_logic::insert_format( $option, 5 );

					TechnicalAnalysis_logic::add_data( $insert_data );

				}

			}

		}

		return $result;

	}


	// 	找出上漲點數，與前日相比

	protected function get_rise_num_value( $data, $key )
	{

		$_this = new self();

		$result = 0;

		if ( !empty($data) && is_array($data) && is_int($key) && isset($data[$key - 1]) ) 
		{

			$result = $data[$key]->close - $data[$key - 1]->close > 0 ? $data[$key]->close - $data[$key - 1]->close : 0 ;

		}

		return $result;

	}


	// 	找出下跌點數，與前日相比

	protected function get_fall_num_value( $data, $key )
	{

		$_this = new self();

		$result = 0;

		if ( !empty($data) && is_array($data) && is_int($key) && isset($data[$key - 1]) ) 
		{

			$result = $data[$key]->close - $data[$key - 1]->close < 0 ? abs($data[$key]->close - $data[$key - 1]->close) : 0 ;

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

			$result = isset($main[$key - 1]) ? $main[$key - 1] + $_this->except( ( $sub[$key] - $main[$key - 1] ), $n ) : $_this->except( array_sum( $sub_data ), $n ) ;

		}

		return $result;

	}


}





