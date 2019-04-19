<?php

namespace App\logic;

use App\Traits\SchemaFunc;
use App\Traits\Mathlib;

class Strategy_logic extends Basetool
{

	use SchemaFunc, Mathlib;

	/*

		策略1

		--買進--

		KD金叉, KD < 20
		&&
		出現下引線( (開盤|收盤) - 最低價 / (開盤|收盤) > 3% )

		金叉日期與下引線日期之間不得超過10天(不考慮工作日)
		
		股價介於 20 - 80塊 之間

		前30日平均成交量 > 500

		--賣出--

		--第一個賣出點--

		1. 成交量當日 > 2500 && 前五日不含本日平均 > 1000 && 出現上引線

		--第二個賣出點--

		2. KD死叉, KD > 80，且(獲利|虧損)超過 5%

		--第三個賣出點--
	
		3. 停損點 5%, 停利點 10%

	*/

	public static function strategy1()
	{

		$_this = new self();

		$result = [];

		$report = [];

		$stragegy_data = [];

		// 日期區間

		$type = 1;

		$start = '2016-01-01';

		$end = '2018-12-31';

		$data = $_this->get_strategy_data( $type, $start, $end );

		$loss_limit = -0.05;

		$profit_limit = 0.1;

		foreach ($data["stock_list"]["data"] as $row) 
		{

			$code = $row->code;

			$KD_data = isset($data["KD_data"][$code]) ? $data["KD_data"][$code] : [] ;

			if ( empty($KD_data) ) 
			{

				continue;
				
			}

			// 股價資料

			$stock_daily_data = isset($data["daily_data"][$code]) ? $data["daily_data"][$code] : [] ;

			$stock_daily_data = collect( $stock_daily_data )->mapWithKeys( function( $item, $key ) {
				return [ $item->data_date => $item ];
			} )->toArray();

			// 金叉點

			$gold_cross = collect( $KD_data["gold_cross"] )->filter( function( $item, $key ) {
				return $item["value1"] < 20 || $item["value2"] < 20;
			} )->mapWithKeys( function( $item, $key ) {
				return [ $item["date"] => $item ];
			})->toArray();

			// 死叉點

			$dead_cross = collect( $KD_data["dead_cross"] )->filter( function( $item, $key ) {
				return $item["value1"] > 80 || $item["value2"] > 80;
			} )->mapWithKeys(function( $item, $key ) {
				return [ $item["date"] => $item ];
			})->toArray();

			// 買進點

			$stragegy_data["buy"] = [];

			$stragegy_data["sell"] = [];

			$detect_buy_date = '';

			$detect_sell_date = '';

			$has_stock = false;

			$volume = 0;

			$volume_cnt = 0;

			$volume_rule = false;

			$cost = 0;

			foreach ($stock_daily_data as $data_date => $item) 
			{

				// 沒成交，跳過

				if ( (int)$item->volume < 1 ) 
				{
					
					continue;
					
				}

				$now_value = $_this->except( floatval($item->highest) + floatval($item->lowest), 2 );

				$diff = $has_stock === true ? $now_value - $cost : 0 ;

				$percent = $has_stock === true ? $_this->except( $diff, $cost ) : 0 ;

				// 偵測金叉日期

				if ( isset($gold_cross[$data_date]) && $has_stock === false ) 
				{

					$detect_buy_date = $data_date;
					
				}

				// 設定買點

				if ( !empty($detect_buy_date) && strtotime($data_date) - strtotime($detect_buy_date) < 86400 * 10 ) 
				{					

					$hasLowerShadows = TechnicalAnalysis_logic::hasLowerShadows( $item );
					
					if ( $hasLowerShadows["status"] === true && $volume_rule["status"] === true && $price_rule === true ) 
					{

						$cost = $_this->except( floatval($item->highest) + floatval($item->lowest), 2 );

						echo "[買進]金叉 && 下引線偵測，代號: {$code}，日期: {$data_date}，買價: {$cost}，前30日平均成交量: " . $volume_rule["avg"] . " <br>";

						$stragegy_data["buy"][$data_date] = 1 ;

						$detect_buy_date = '';

						$has_stock = true;

						$volume = 0;

						$volume_cnt = 0;

						$volume_rule = [];
						
					}

					$volume_rule = $_this->volume_rule_match( $stock_daily_data, $data_date, $days = 30, $target = 500 );

					$price_rule = $now_value >= 20 && $now_value <= 80;

				}

				// 成交量 && 上引線偵測(第一賣出點)

				if ( $has_stock === true ) 
				{

					$hasUpperShadows = TechnicalAnalysis_logic::hasUpperShadows( $item );

					if ( $hasUpperShadows === true && $volume_rule === true && $item->volume > 2500 ) 
					{

						echo "[賣出]成交量 && 上引線偵測，代號: {$code}，日期: {$data_date}，買價: {$cost}，賣價: " . $now_value . "，價差: ". $diff ."，利率: ". round($percent, 2) ." <br>";

						$volume = 0;

						$volume_cnt = 0;

						$volume_rule = false;

						$stragegy_data["sell"][$data_date] = 1 ;

						$has_stock = false;

						$cost = 0;

					}

					$volume += (int)$item->volume;

					$volume_cnt++;

					$volume_rule = $volume_cnt > 4 && $volume / 5 > 1000 ;
					
				}

				// 偵測死叉日期(第二賣出點)

				if ( isset($dead_cross[$data_date]) && $has_stock === true && abs($percent) > 0.05 ) 
				{

					echo "[賣出]死叉偵測，代號: {$code}，日期: {$data_date}，買價: {$cost}，賣價: " . $now_value . "，價差: ". $diff ."，利率: ". round($percent, 2) ." <br>";

					$volume = 0;

					$volume_cnt = 0;

					$volume_rule = false;

					$stragegy_data["sell"][$data_date] = 1 ;

					$has_stock = false;

					$cost = 0;

				}

				// 抵達停利點 || 抵達停損點

				if ( $has_stock === true ) 
				{

					if ( $percent >= $profit_limit ) 
					{

						echo "[賣出]抵達停利點，代號: {$code}，日期: {$data_date}，買價: {$cost}，賣價: " . $now_value . "，價差: ". $diff ."，利率: ". round($percent, 2) ." <br>";

						$volume = 0;

						$volume_cnt = 0;

						$volume_rule = false;

						$stragegy_data["sell"][$data_date] = 1 ;

						$has_stock = false;

						$cost = 0;

					}

					if ( $percent <= $loss_limit ) 
					{

						echo "[賣出]抵達停損點，代號: {$code}，日期: {$data_date}，買價: {$cost}，賣價: " . $now_value . "，價差: ". $diff ."，利率: ". round($percent, 2) ." <br>";

						$volume = 0;

						$volume_cnt = 0;

						$volume_rule = false;

						$stragegy_data["sell"][$data_date] = 1 ;

						$has_stock = false;

						$cost = 0;

					}
					
				}

			}

			// 交易

			$trade_log = $_this->simulate_trade( $stock_daily_data, $stragegy_data );

			// 計算各股投資報酬率

			$report[$code] = $_this->countReturnOnInvestment( $trade_log );

			// $limit++;

		}

		// 回報總報酬率

		$result = [
			"detail" 	=> $report,
			"year" 		=> $_this->getYearReport( $report ),
			"total" 	=> $_this->getTotalReport( $report ),
		];

		// $diff = time() - $startt;

		// echo $diff;

		dd($result);

	}


	// 		策略資料

	public static function get_strategy_data( $type, $start, $end )
	{

		$result = [];

		switch ( $type ) 
		{

			case 1:

				$result = [
					"stock_list" 	=> Stock_logic::get_list(),
					"daily_data" 	=> Stock_logic::get_stock_data_by_date_range( $start, $end ),
					"KD_data" 		=> TechnicalAnalysis_logic::get_cross_sign( 1, $start, $end )
				];

				break;

		}

		return $result;

	}


	// 		模擬交易

	protected function simulate_trade( $daily_data, $stragegy_data )
	{

		$_this = new self();

		$result = [
			"buy" 	=> [],
			"sell" 	=> [],
		];

		if ( !empty($daily_data) && is_array($daily_data) && !empty($stragegy_data) && is_array($stragegy_data) ) 
		{

			// 持有股票

			$has_stock = false;

			// 模擬交易

			foreach ($daily_data as $row) 
			{

				// 存在於金叉清單

				if ( isset($stragegy_data["buy"][$row->data_date]) && $has_stock === false ) 
				{

					// 假設都買在最高價 - 最低價除以2的位置

					$result["buy"][] = [
						"date" 	=> $row->data_date,
						"value" => $_this->except( floatval($row->highest) + floatval($row->lowest), 2 )
					];

					$has_stock = true;

				}

				if ( isset($stragegy_data["sell"][$row->data_date]) && $has_stock === true ) 
				{

					// 假設都賣在最高價 - 最低價除以2的位置

					$result["sell"][] = [
						"date" 	=> $row->data_date,
						"value" => $_this->except( floatval($row->highest) + floatval($row->lowest), 2 )
					];

					$has_stock = false;

				}

			}

			
		}

		return $result;

	}


	// 		計算各股投資報酬率，不含手續費與交易稅

	protected function countReturnOnInvestment( $data )
	{

		$_this = new self();

		$result = [
			"log" 		=> [],
			"total"		=> [
				"profit" 	=> 0,
				"cost" 		=> 0,
				"percent" 	=> 0,			
			],
		];

		if ( !empty($data) && is_array($data) ) 
		{

			foreach ($data["sell"] as $key => $item) 
			{

				$buy = $data["buy"][$key]["value"];

				$sell = $item["value"];

				$diff = $sell - $buy;

				$percent = $_this->except( $diff, $buy );

				$result["total"]["profit"] += $diff;

				$result["total"]["cost"] += $buy;

				$result["log"][] = [
					"date" 		=> $item["date"],
					"buy" 		=> $buy,
					"sell" 		=> $sell,
					"diff" 		=> $diff,
					"percent" 	=> $percent,
				];

			}

			$result["total"]["percent"] = $_this->except( $result["total"]["profit"], $result["total"]["cost"] );
			
		}

		return $result;

	}


	// 		取得年報酬率

	protected function getYearReport( $data )
	{

		$_this = new self();

		$result = [];

		if ( !empty($data) && is_array($data) ) 
		{

			foreach ($data as $item) 
			{

				foreach ($item["log"] as $row) 
				{

					$year = date("Y", strtotime($row["date"]));

					$result[$year]["cost"] = isset($result[$year]["cost"]) ? $result[$year]["cost"] : 0 ;
					$result[$year]["profit"] = isset($result[$year]["profit"]) ? $result[$year]["profit"] : 0 ;

					$result[$year]["cost"] += $row["buy"];
					$result[$year]["profit"] += $row["diff"];

				}

			}

			foreach ($result as $year => &$row) 
			{
 
				$row["percent"] = $_this->except( $row["profit"], $row["cost"] );

			}

		}

		ksort($result);

		return $result;

	}


	// 		取得總報酬率

	protected function getTotalReport( $data )
	{

		$_this = new self();

		$result = [
			"cost" 		=> 0,
			"profit" 	=> 0,
			"percent" 	=> 0,
		];

		if ( !empty($data) && is_array($data) ) 
		{

			foreach ($data as $row) 
			{

				$result["cost"] += $row["total"]["cost"] ;
				$result["profit"] += $row["total"]["profit"] ;

			}

			$result["percent"] = $_this->except( $result["profit"], $result["cost"] );
			
		}

		return $result;

	}


	// 		成交量條件

	protected function volume_rule_match( $stock_daily_data, $data_date, $days, $target )
	{

		$result = [];

		if ( !empty($stock_daily_data) && is_array($stock_daily_data) && !empty($data_date) && is_string($data_date) && !empty($days) && !empty($target) ) 
		{

			$index = 0;

			foreach ($stock_daily_data as $key => $value) 
			{

				$index++;

				if ( $key === $data_date ) 
				{

					break;
					
				}

			}

			$start = $index - $days > 0 ? $index - $days : 0 ;

			$sub_array = array_slice($stock_daily_data, $start, $days);

			$avg = collect( $sub_array )->pluck( "volume" )->map( function( $item, $key ) {
				return intval($item);
			} )->avg();
			
			$result = [
				"status" => $avg > $target,
				"avg"	 => intval($avg)
			];

		}

		return $result;

	}





}
