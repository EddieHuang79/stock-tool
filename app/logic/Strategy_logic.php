<?php

namespace App\logic;

use App\Traits\SchemaFunc;
use App\Traits\Mathlib;
use App\model\Strategy;
use Illuminate\Support\Facades\Storage;

class Strategy_logic
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

	public function strategy1()
	{

		$report = [];

        $strategy_data = [];

		// 日期區間

		$type = 1;

		$start = '2016-01-01';

		$end = '2018-12-31';

		$data = $this->get_strategy_data( $type, $start, $end );

		$loss_limit = -0.05;

		$profit_limit = 0.1;

        $Tech = TechnicalAnalysis_logic::getInstance();

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

			$stock_daily_data = collect( $stock_daily_data )->mapWithKeys( function( $item ) {
				return [ $item->data_date => $item ];
			} )->toArray();

			// 金叉點

			$gold_cross = collect( $KD_data["gold_cross"] )->filter( function( $item ) {
				return $item["value1"] < 20 || $item["value2"] < 20;
			} )->mapWithKeys( function( $item ) {
				return [ $item["date"] => $item ];
			})->toArray();

			// 死叉點

			$dead_cross = collect( $KD_data["dead_cross"] )->filter( function( $item ) {
				return $item["value1"] > 80 || $item["value2"] > 80;
			} )->mapWithKeys(function( $item ) {
				return [ $item["date"] => $item ];
			})->toArray();

			// 買進點

            $strategy_data["buy"] = [];

            $strategy_data["sell"] = [];

			$detect_buy_date = '';

			$has_stock = false;

			$volume = 0;

			$volume_cnt = 0;

			$volume_rule = false;

            $price_rule = false;

			$cost = 0;

			foreach ($stock_daily_data as $data_date => $item)
			{

				// 沒成交，跳過

				if ( (int)$item->volume < 1 )
				{

					continue;

				}

				$now_value = $this->except( floatval($item->highest) + floatval($item->lowest), 2 );

				$diff = $has_stock === true ? $now_value - $cost : 0 ;

				$percent = $has_stock === true ? $this->except( $diff, $cost ) : 0 ;

				// 偵測金叉日期

				if ( isset($gold_cross[$data_date]) && $has_stock === false )
				{

					$detect_buy_date = $data_date;

				}

				// 設定買點

				if ( !empty($detect_buy_date) && strtotime($data_date) - strtotime($detect_buy_date) < 86400 * 10 )
				{

					$hasLowerShadows = $Tech->hasLowerShadows( $item );

					if ( $hasLowerShadows["status"] === true && $volume_rule["status"] === true && $price_rule === true )
					{

						$cost = $this->except( floatval($item->highest) + floatval($item->lowest), 2 );

						echo "[買進]金叉 && 下引線偵測，代號: {$code}，日期: {$data_date}，買價: {$cost}，前30日平均成交量: " . $volume_rule["avg"] . " <br>";

                        $strategy_data["buy"][$data_date] = 1 ;

						$detect_buy_date = '';

						$has_stock = true;

						$volume = 0;

						$volume_cnt = 0;

					}

					$volume_rule = $this->volume_rule_match( $stock_daily_data, $data_date, $days = 30, $target = 500 );

					$price_rule = $now_value >= 20 && $now_value <= 80;

				}

				// 成交量 && 上引線偵測(第一賣出點)

				if ( $has_stock === true )
				{

					$hasUpperShadows = $Tech->hasUpperShadows( $item );

					if ( $hasUpperShadows === true && $volume_rule === true && $item->volume > 2500 )
					{

						echo "[賣出]成交量 && 上引線偵測，代號: {$code}，日期: {$data_date}，買價: {$cost}，賣價: " . $now_value . "，價差: ". $diff ."，利率: ". round($percent, 2) ." <br>";

						$volume = 0;

						$volume_cnt = 0;

                        $strategy_data["sell"][$data_date] = 1 ;

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

                    $strategy_data["sell"][$data_date] = 1 ;

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

                        $strategy_data["sell"][$data_date] = 1 ;

						$has_stock = false;

						$cost = 0;

					}

					if ( $percent <= $loss_limit )
					{

						echo "[賣出]抵達停損點，代號: {$code}，日期: {$data_date}，買價: {$cost}，賣價: " . $now_value . "，價差: ". $diff ."，利率: ". round($percent, 2) ." <br>";

						$volume = 0;

						$volume_cnt = 0;

						$volume_rule = false;

                        $strategy_data["sell"][$data_date] = 1 ;

						$has_stock = false;

						$cost = 0;

					}

				}

			}

			// 交易

			$trade_log = $this->simulate_trade( $stock_daily_data, $strategy_data );

			// 計算各股投資報酬率

			$report[$code] = $this->countReturnOnInvestment( $trade_log );

		}

		// 回報總報酬率

		$result = [
			"detail" 	=> $report,
			"year" 		=> $this->getYearReport( $report ),
			"total" 	=> $this->getTotalReport( $report ),
		];

		dd($result);

	}


	/*

		策略2

		原文：MACD 最高（最低）後，轉折連續兩天或三天低於（高於）前日，隔一天放空（做多）

		MACD達到最高之後，轉折連續兩天或三天低於前日，為目標賣出訊號

		MACD達到最低之後，轉折連續兩天或三天低於前日，為目標買進訊號

	*/

//	public function strategy2()
//	{
//
//		$start = '2016-01-01';
//
//		$end = '2018-12-31';
//
//		$data = Stock_logic::getInstance()->get_list();
//
//        $Tech = TechnicalAnalysis_logic::getInstance();
//
//		foreach ($data["data"] as $item)
//		{
//
//			$code = $item->code;
//
//			$daily_data = Stock_logic::getInstance()->get_stock_data_by_date_range( $start, $end, $code );
//
//			$stock_data = isset($daily_data[$code]) ? $daily_data[$code] : [];
//
//			$highest = 0;
//
//			$status = '';
//
//			$smaller_index = 0;
//
//			$trade_data[$code] = [
//				"buy" 	=> [],
//				"sell" 	=> []
//			];
//
//			$strategy_data = [];
//
//			$trade_result[$code] = [];
//
//			$has_stock = false;
//
//			$trade_index = 0;
//
//			foreach ($stock_data as $row)
//			{
//
//				$MACD = $Tech->get_data( $type = 8, [$row->id] );
//
//				if ( $MACD->isNotEmpty() )
//				{
//
//					$value = floatval($MACD[0]->value);
//
//					// 指標如果直接負轉正或是正轉負，reset判斷值
//
//					$now_status = $value > 0;
//
//					if ( $now_status !== $status )
//					{
//
//						$smaller_index = 0;
//
//						$highest = 0;
//
//					}
//
//					$status = $value > 0;
//
//					if ( abs($value) >= $highest )
//					{
//
//						$highest = abs($value);
//
//						$smaller_index = 0;
//
//					}
//					else
//					{
//
//						$smaller_index++;
//
//					}
//
//					if ( $smaller_index > 2 )
//					{
//
//						$trade_type = $status === true ? "sell" : "buy" ;
//
//						$price = $this->except( floatval($row->highest) + floatval($row->lowest), 2 );
//
//						$trade_data[$code][$trade_type][] = [
//							"data_date" => $row->data_date,
//							"price" 	=> $price,
//							"MACD" 		=> $value,
//						];
//
//						if ( $has_stock === false && $trade_type === "buy"  )
//						{
//
//							$has_stock = true;
//
//							$trade_result[$code][$trade_index] = [
//								"buy_date" 			=> $row->data_date,
//								"buy_price" 		=> $price,
//								"sell_date" 		=> '',
//								"sell_price" 		=> '',
//								"profie" 			=> '',
//								"percent" 			=> ''
//							];
//
//                            $strategy_data["buy"][$row->data_date] = 1;
//
//						}
//
//						if ( $has_stock === true && $trade_type === "sell" )
//						{
//
//							$trade_result[$code][$trade_index]["sell_date"] = $row->data_date;
//
//							$trade_result[$code][$trade_index]["sell_price"] = $price;
//
//							$trade_result[$code][$trade_index]["profie"] = $trade_result[$code][$trade_index]["sell_price"] - $trade_result[$code][$trade_index]["buy_price"];
//
//							$trade_result[$code][$trade_index]["percent"] = $this->except( $trade_result[$code][$trade_index]["profie"], $trade_result[$code][$trade_index]["buy_price"] );
//
//							$trade_result[$code][$trade_index]["percent"] = round($trade_result[$code][$trade_index]["percent"] * 100, 2) . '%';
//
//							$trade_index++;
//
//							$has_stock = false;
//
//                            $strategy_data["sell"][$row->data_date] = 1;
//
//						}
//
//						$smaller_index = 0;
//
//						$highest = 0;
//
//					}
//
//				}
//
//			}
//
//			// 交易
//
//			$trade_log = $this->simulate_trade( $stock_data, $strategy_data );
//
//			// 計算各股投資報酬率
//
//			$report[$code] = $this->countReturnOnInvestment( $trade_log );
//
//		}
//
//		// 回報總報酬率
//
//		$result = [
//			"detail" 	=> $report,
//			"year" 		=> $this->getYearReport( $report ),
//			"total" 	=> $this->getTotalReport( $report ),
//		];
//
//		dd($result);
//
//	}


    /*

        策略3

        尋找布林通道BB% >= 0.8以上的股票，超過就買進，跌到0.8以下就賣掉

    */

    public function strategy3()
    {

        $start = '2016-01-01';

        $end = '2018-12-31';

        $Tech = TechnicalAnalysis_logic::getInstance();

        $Stock = Stock_logic::getInstance();

        $file_name = 'strategy3.txt';

        if ( file_exists( storage_path( 'app/' . $file_name ) ) === false )
        {

            Storage::put( $file_name , '');

        }

        $ori_file_content = Storage::get( $file_name );

        $file_content = explode("\n", $ori_file_content);

        $file_content = array_filter( $file_content, "trim" );

        $last = explode(",", end($file_content));

        $last_code = $last[0];

        //  股價為 -- 的項目會計算上有誤差，撈出來排除掉

        $not_read = $Stock->get_stock_by_none_price()->pluck("code")->toArray();

        $data = $Stock->get_all_stock_info()->filter(function ($item) use($last_code, $not_read) {
            return $item->code > $last_code && !in_array( $item->code, $not_read ) ;
        })->forPage(0, 5)->map(function ($item) use( $Stock, $Tech, $start, $end ) {

            try
            {

                $result = [];

                $Stock_data = $Stock->get_stock_data_by_date_range( $start, $end, $item->code );

                $Stock_data = collect( $Stock_data[$item->code] )->mapWithKeys(function ($item) {
                    return [
                        $item->data_date => $this->except( $item->highest + $item->lowest, 2 )
                    ];
                })->toArray();

                $sum = array_sum( $Stock_data );

                $cnt = count( $Stock_data );

                $avg = round( $sum/$cnt, 2 );

                //  價格太低的濾掉

                if ( $avg < 30 )
                {
                    throw new \Exception( "均價低於30" );
                }

                $Tech_data = $Tech->get_data( $item->id )->filter( function ($item) use( $Stock, $Tech, $start, $end ) {
                    return $item->step === 4 && $item->percentB !== 0.0 && $start <= $item->data_date && $item->data_date <= $end;
                } )->map(function ($item) {
                    return [
                        "data_date" => $item->data_date,
                        "percentB"  => $item->percentB
                    ];
                })->values()->toArray();

                $buy_date = [];

                $sell_date = [];

                $has_stock = false;

                foreach ($Tech_data as $row )
                {

                    if ( $row["percentB"] >= 0.8 && $has_stock === false )
                    {

                        $has_stock = true;

                        $buy_date[] = $row;

                    }

                    if ( $row["percentB"] < 0.8 && $has_stock === true )
                    {

                        $has_stock = false;

                        $sell_date[] = $row;

                    }


                }

                //  沒資料

                if ( empty($buy_date) )
                {

                    throw new \Exception( "沒有符合的percentB資料" );

                }

                foreach ( $buy_date as $key => $row )
                {

                    //  日期不對的過濾掉

                    if ( !isset($Stock_data[$row["data_date"]]) )
                    {

                        throw new \Exception( "買進日期比對失敗" );
                    }

                    if ( !isset($Stock_data[$sell_date[$key]["data_date"]]) )
                    {

                        throw new \Exception( "賣出日期比對失敗" );
                    }

                    $result[] = implode(",", [
                        "code"          =>  $item->code,
                        "buy_date"      =>  $row["data_date"],
                        "buy_percentB"  =>  $row["percentB"],
                        "buy_price"     =>  $Stock_data[$row["data_date"]],
                        "sell_date"     =>  $sell_date[$key]["data_date"],
                        "sell_percentB" =>  $sell_date[$key]["percentB"],
                        "sell_price"    =>  $Stock_data[$sell_date[$key]["data_date"]],
                        "diff"          =>  $Stock_data[$sell_date[$key]["data_date"]] - $Stock_data[$row["data_date"]],
                        "error"         =>  "Correct"
                    ]);

                }

            }
            catch (\Exception $e)
            {

                return [implode(",", [
                    "code"          =>  $item->code,
                    "buy_date"      =>  '-',
                    "buy_percentB"  =>  '-',
                    "buy_price"     =>  0,
                    "sell_date"     =>  '-',
                    "sell_percentB" =>  '-',
                    "sell_price"    =>  0,
                    "diff"          =>  0,
                    "error"         =>  $e->getMessage()
                ])];

            }

            return $result;

        })->values()->toArray();

        $content = $ori_file_content;

        foreach ($data as $row)
        {

            foreach ($row as $row1)
            {

                $content .= $row1 . "\n" ;

            }

        }

        try{

            Storage::put( $file_name , $content);

        }
        catch (\Exception $e)
        {

            dd($e);

        }



        return true;

    }


	// 		策略資料

	public function get_strategy_data( $type, $start, $end )
	{

		$result = [];

		switch ( $type )
		{

			case 1:

				$result = [
					"daily_data" 	=> Stock_logic::getInstance()->get_stock_data_by_date_range( $start, $end ),
					"KD_data" 		=> TechnicalAnalysis_logic::getInstance()->get_cross_sign( 1, $start, $end )
				];

				break;

			case 2:

				$result = [
                    "daily_data" 	=> Stock_logic::getInstance()->get_stock_data_by_date_range( $start, $end ),
                    "KD_data" 		=> TechnicalAnalysis_logic::getInstance()->get_cross_sign( 1, $start, $end )
                ];

				break;

		}

		return $result;

	}


	// 		模擬交易

	private function simulate_trade( $daily_data, $strategy_data )
	{

		$result = [
			"buy" 	=> [],
			"sell" 	=> [],
		];

		if ( !empty($daily_data) && is_array($daily_data) && !empty($strategy_data) && is_array($strategy_data) )
		{

			// 持有股票

			$has_stock = false;

			// 模擬交易

			foreach ($daily_data as $row)
			{

				// 存在於金叉清單

				if ( isset($strategy_data["buy"][$row->data_date]) && $has_stock === false )
				{

					// 假設都買在最高價 - 最低價除以2的位置

					$result["buy"][] = [
						"date" 	=> $row->data_date,
						"value" => $this->except( floatval($row->highest) + floatval($row->lowest), 2 )
					];

					$has_stock = true;

				}

				if ( isset($strategy_data["sell"][$row->data_date]) && $has_stock === true )
				{

					// 假設都賣在最高價 - 最低價除以2的位置

					$result["sell"][] = [
						"date" 	=> $row->data_date,
						"value" => $this->except( floatval($row->highest) + floatval($row->lowest), 2 )
					];

					$has_stock = false;

				}

			}


		}

		return $result;

	}


	// 		計算各股投資報酬率，不含手續費與交易稅

    private function countReturnOnInvestment( $data )
	{

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

				$percent = $this->except( $diff, $buy );

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

			$result["total"]["percent"] = $this->except( $result["total"]["profit"], $result["total"]["cost"] );

		}

		return $result;

	}


	// 		取得年報酬率

    private function getYearReport( $data )
	{

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

				$row["percent"] = $this->except( $row["profit"], $row["cost"] );

			}

		}

		ksort($result);

		return $result;

	}


	// 		取得總報酬率

    private function getTotalReport( $data )
	{

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

			$result["percent"] = $this->except( $result["profit"], $result["cost"] );

		}

		return $result;

	}


	// 		成交量條件

    private function volume_rule_match( $stock_daily_data, $data_date, $days, $target )
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

			$avg = collect( $sub_array )->pluck( "volume" )->map( function( $item ) {
				return intval($item);
			} )->avg();

			$result = [
				"status" => $avg > $target,
				"avg"	 => intval($avg)
			];

		}

		return $result;

	}


	// 		取得策略列表

	public function get_list()
	{

		return Strategy::getInstance()->get_list();

	}


	// 		取得策略訂閱id

	public function get_rel_id( $type, $strategy_id )
	{

		$result = [];

		if ( !empty($type) && is_int($type) && !empty($strategy_id) && is_int($strategy_id) )
		{

			$result = Strategy::getInstance()->get_rel_id( $type, $strategy_id );

		}

		return $result;

	}


	// 		寫入策略主檔

	public function add_data( $data )
	{

		$result = false;

		if ( !empty($data) && is_array($data) )
		{

			$result = Strategy::getInstance()->add_data( $data );

		}

		return $result;

	}


	// 		寫入策略訂閱關聯

	public function add_rel_data()
	{

		$result = false;

		if ( !empty($data) && is_array($data) )
		{

			$result = Strategy::getInstance()->add_rel_data( $data );

		}

		return $result;

	}


	//      Instance

    public static function getInstance()
    {

        return new self;

    }

}
