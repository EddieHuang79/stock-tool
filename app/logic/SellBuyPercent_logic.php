<?php

namespace App\logic;

use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\Storage;
use File;
use App\model\SellBuyPercent;
use App\Traits\SchemaFunc;

class SellBuyPercent_logic extends Basetool
{

	use SchemaFunc;

	protected $parents_dir = 'stock';


	/*  資料庫操作  */

	// 		判斷資料是否已寫入，回傳日期陣列

	protected function check_data_repeat( $code )
	{

		$_this = new self();

		$result = [];

		if ( !empty($code) ) 
		{
			
			$data = $_this->get_statistics( $code );

			$result = $_this->pluck( $data, $key = 'data_date' );
			
		}

		return $result;

	}


	// 		取得統計數據

	protected function get_statistics( $code )
	{

		$result = [];

		if ( !empty($code) ) 
		{

			$stock_data = Stock_logic::get_stock( $code );

			$result = SellBuyPercent::get_statistics( $stock_data->id );

		}

		return $result;

	}


	// 		寫入資料

	public static function add_sell_buy_percent_data( $data )
	{

		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{

			$result = SellBuyPercent::add_sell_buy_percent_data( $data );

		}

		return $result ;

	}


	// 		編輯資料

	public static function edit_sell_buy_percent_data( $data, $id )
	{

		$result = false;

		if ( !empty($data) && is_array($data) && !empty($id) && is_int($id) ) 
		{

			SellBuyPercent::edit_sell_buy_percent_data( $data, $id );

			$result = true;

		}

		return $result;

	}


	// 		取得第一筆資料日期

	public static function get_first_data_time()
	{

		return SellBuyPercent::get_first_data_time();

	}


	// 		取得最後更新日期

	public static function get_last_update_time()
	{

		return SellBuyPercent::get_last_update_time();

	}


	// 		取得各股票需要計算的資料量

	public static function get_all_stock_count_data_num()
	{

		$stock_data = SellBuyPercent::get_all_statistics_status();

		$result = collect( $stock_data )->filter(function( $item, $key ) {
					return is_null( $item->result );
				})->mapToGroups(function ($item, $key) {
					return [$item->code => $item->data_date];
				})->mapWithKeys(function ($item, $key) {
					return [$key => $item->count()];
				})->toArray();

		return $result;

	}


	/* 資料流程 */


	// 		預先建立報表資料
	/*
		
			邏輯之後再整合			
	
	*/

	protected function create_data( $code )
	{

		$_this = new self();

		$statistics_data = $_this->get_statistics( $code );

		$main = collect( $statistics_data )->filter(function( $item, $key ) {
			return is_null($item->id);
		})->toArray();

		foreach ($main as $row) 
		{

			$insert_data = [
				"stock_data_id" 		=> $row->data_id,
				"spread" 				=> "",
				"buy1" 					=> "",
				"sell1"					=> "",
				"buy2" 					=> "",
				"sell2" 				=> "",
				"rally_total" 			=> "",
				"tumbled_total" 		=> "",
				"rally_num1"		 	=> "",
				"tumbled_num1" 			=> "",
				"rally_total_20days" 	=> "",
				"tumbled_total_20days" 	=> "",
				"result" 				=> "",
				"created_at" 			=> date("Y-m-d H:i:s"),
				"updated_at" 			=> date("Y-m-d H:i:s"),
			];

			$_this->add_sell_buy_percent_data( $insert_data );

		}

		return true;

	}


	// 		計算收盤成交價差
	// 		公式: 當日收盤 - 前日收盤 

	protected function count_spread( $code )
	{

		$_this = new self();

		$result = false;

		if ( !empty($code) && is_string($code) ) 
		{

			$insert_data = [];

			$data = $_this->get_statistics( $code );

			foreach ($data as $key => $row) 
			{

				if ( $key === 0 || $row->spread !== '' ) 
				{

					continue;
					
				}

				// 前日資料

				$yesterday_data = $data[$key - 1];

				// 前日收盤

				$last_close = floatval($yesterday_data->close);

				// 今日收盤

				$today_close = floatval($row->close);

				$spread = round( $today_close - $last_close, 2 );

				// 更新資料

				$_this->edit_sell_buy_percent_data( ["spread" => $spread, "updated_at" => date("Y-m-d H:i:s")], $row->id );

			}

			$result = true;
			
		}

		return $result;

	}


	// 		計算買盤1
	// 		公式:
	/*
			若 今日收盤價 > 昨日收盤價
			買盤1 = 今日開盤價 - 昨日收盤價
			若 今日收盤價 <= 昨日收盤價
			買盤1 = 今日最高價 - 今日開盤價
	*/

	protected function count_buy1( $code )
	{

		$_this = new self();

		$result = false;

		if ( !empty($code) && is_string($code) ) 
		{

			$insert_data = [];

			$data = $_this->get_statistics( $code );

			foreach ($data as $key => $row) 
			{

				if ( $key === 0 || $row->buy1 !== '' ) 
				{

					continue;
					
				}

				// 前日資料

				$yesterday_data = $data[$key - 1];

				// 前日收盤

				$last_close = floatval($yesterday_data->close);

				// 今日收盤

				$today_close = floatval($row->close);

				$buy1 = $today_close > $last_close ? round( floatval($row->open) - $last_close, 2) : round( floatval($row->highest) - floatval($row->open), 2 ) ;

				// 更新資料

				$_this->edit_sell_buy_percent_data( ["buy1" => $buy1, "updated_at" => date("Y-m-d H:i:s")], $row->id );

			}

			$result = true;
			
		}

		return $result;

	}


	// 		計算賣盤1
	// 		公式:
	/*
			若 今日收盤價 > 昨日收盤價
			賣盤1 = 今日開盤價 - 今日最低價
			若 今日收盤價 <= 昨日收盤價
			賣盤1 = 昨日收盤價 - 今日開盤價
	*/

	protected function count_sell1( $code )
	{

		$_this = new self();

		$result = false;

		if ( !empty($code) && is_string($code) ) 
		{

			$insert_data = [];

			$data = $_this->get_statistics( $code );

			foreach ($data as $key => $row) 
			{

				if ( $key === 0 || $row->sell1 !== '' ) 
				{

					continue;
					
				}

				// 前日資料

				$yesterday_data = $data[$key - 1];

				// 前日收盤

				$last_close = floatval($yesterday_data->close);

				// 今日收盤

				$today_close = floatval($row->close);

				$sell1 = $today_close > $last_close ? round( floatval($row->open) - floatval($row->lowest), 2) : round( $last_close - floatval($row->open), 2 ) ;

				// 更新資料

				$_this->edit_sell_buy_percent_data( ["sell1" => $sell1, "updated_at" => date("Y-m-d H:i:s")], $row->id );

			}

			$result = true;

		}

		return $result;

	}


	// 		計算買盤2
	// 		公式:
	/*
			若 今日收盤價 > 昨日收盤價
			買盤2 = 今日最高價 - 今日最低價
			若 今日收盤價 <= 昨日收盤價
			買盤2 = 今日收盤價 - 今日最低價
	*/

	protected function count_buy2( $code )
	{

		$_this = new self();

		$result = false;

		if ( !empty($code) && is_string($code) ) 
		{

			$insert_data = [];

			$data = $_this->get_statistics( $code );

			foreach ($data as $key => $row) 
			{

				if ( $key === 0 || $row->buy2 !== '' ) 
				{

					continue;
					
				}

				// 前日資料

				$yesterday_data = $data[$key - 1];

				// 前日收盤

				$last_close = floatval($yesterday_data->close);

				// 今日收盤

				$today_close = floatval($row->close);

				$buy2 = $today_close > $last_close ? round( floatval($row->highest) - floatval($row->lowest), 2) : round( $today_close - floatval($row->lowest), 2 ) ;


				// 更新資料
				$_this->edit_sell_buy_percent_data( ["buy2" => $buy2, "updated_at" => date("Y-m-d H:i:s")], $row->id );

			}

			return true;

		}

		return $result;

	}


	// 		計算賣盤2
	// 		公式:
	/*
			若 今日收盤價 > 昨日收盤價
			賣盤2 = 今日最高價 - 今日收盤價
			若 今日收盤價 <= 昨日收盤價
			賣盤2 = 今日最高價 - 今日最低價
	*/

	protected function count_sell2( $code )
	{

		$_this = new self();

		$result = false;

		if ( !empty($code) && is_string($code) ) 
		{

			$insert_data = [];

			$data = $_this->get_statistics( $code );

			foreach ($data as $key => $row) 
			{

				if ( $key === 0 || $row->sell2 !== '' ) 
				{

					continue;
					
				}

				// 前日資料

				$yesterday_data = $data[$key - 1];

				// 前日收盤

				$last_close = floatval($yesterday_data->close);

				// 今日收盤

				$today_close = floatval($row->close);

				$sell2 = $today_close > $last_close ? round( floatval($row->highest) - $today_close, 2) : round( floatval($row->highest) - floatval($row->lowest), 2 ) ;

				// 更新資料

				$_this->edit_sell_buy_percent_data( ["sell2" => $sell2, "updated_at" => date("Y-m-d H:i:s")], $row->id );

			}

			$result = true;

		}

		return $result;

	}


	// 		計算漲幅總和、跌幅總和、買盤力道張數、賣盤力道張數
	// 		公式:
	/*
			漲幅總和 = 買盤1+買盤2
			跌幅總和 = 賣盤1+賣盤2
			買盤力道張數 = 成交量 * ( 漲幅總和 / ( 漲幅總和+跌幅總和) )
			賣盤力道張數 = 成交量 * ( 跌幅總和 / ( 漲幅總和+跌幅總和) )
	*/

	protected function count_pro_data( $code )
	{

		$_this = new self();

		$result = false;

		if ( !empty($code) && is_string($code) ) 
		{

			$insert_data = [];

			$data = $_this->get_statistics( $code );

			foreach ($data as $key => $row) 
			{

				if ( $key === 0 || ( $row->rally_total !== '' && $row->tumbled_total !== '' && $row->rally_num1 !== '' && $row->tumbled_num1 !== '' ) ) 
				{

					continue;
					
				}

				// 漲幅總和

				$rally_total = round($row->buy1 + $row->buy2, 2) ;

				// 跌幅總和

				$tumbled_total = round($row->sell1 + $row->sell2, 2) ;

				$sum = $rally_total + $tumbled_total;

				// 買盤力道張數

				$rally_num1 = $sum > 0 ? round( $row->volume * ( $rally_total / ($rally_total + $tumbled_total ) ), 2 ) : 0;

				// 賣盤力道張數

				$tumbled_num1 = $sum > 0 ? round( $row->volume * ( $tumbled_total / ($rally_total + $tumbled_total ) ), 2 ) : 0;

				// 更新資料

				$_this->edit_sell_buy_percent_data( ["rally_total" => $rally_total, "tumbled_total" => $tumbled_total, "rally_num1" => $rally_num1, "tumbled_num1" => $tumbled_num1, "updated_at" => date("Y-m-d H:i:s")], $row->id );

			}

			$result = true;

		}

		return $result;

	}


	// 		計算20天總買盤、20天總賣盤、買賣壓力道比例
	// 		公式:
	/*
			20天總買盤 = 買盤力道張數過去20天加總
			20天總賣盤 = 賣盤力道張數過去20天加總
			買賣壓力道比例 = 20天總賣盤/20天總買盤
	*/

	protected function count_20days_data_and_result( $code )
	{

		$_this = new self();

		$result = false;

		if ( !empty($code) && is_string($code) ) 
		{

			$insert_data = [];

			$data = $_this->get_statistics( $code );

			foreach ($data as $key => $row) 
			{

				if ( $key < 20 || ( $row->rally_total_20days !== '' && $row->tumbled_total_20days !== '' ) ) 
				{

					continue;
					
				}

				// 20天總買盤

				$start_key = $key - 19;

				$end_key = $key;

				$rally_total_20days = collect( $data )->pluck('rally_num1')->filter(function ($item, $key) use($start_key, $end_key) {
					return $start_key <= $key && $key <= $end_key;
				})->sum();

				// 20天總賣盤

				$tumbled_total_20days = collect( $data )->pluck('tumbled_num1')->filter(function ($item, $key) use($start_key, $end_key) {
					return $start_key <= $key && $key <= $end_key;
				})->sum();

				// 買賣壓力道比例

				$result = $rally_total_20days > 0 ? $tumbled_total_20days/$rally_total_20days : 0 ;

				// 更新資料

				$_this->edit_sell_buy_percent_data( ["rally_total_20days" => $rally_total_20days, "tumbled_total_20days" => $tumbled_total_20days, "result" => $result, "updated_at" => date("Y-m-d H:i:s")], $row->id );

			}

			$result = true;
			
		}

		return $result;

	}


	// 		輸出成報表

	public static function get_buy_sell_report( $request )
	{

		$_this = new self();

		$txt = __('base');

		$result = [
			"error" => false,
			"data" 	=> []
		];

		try 
		{

			$code = $request->code;

			if ( empty($code) ) 
			{
				
				throw new \Exception( $txt["auth_error"] );
				
			}
		
			$data = $_this->get_statistics( $code );

			if ( $data->count() < 1 ) 
			{
				
				throw new \Exception( $txt["find_nothing"] );
				
			}

			$data = collect( $data )->filter(function ($item, $key) {
						return $item->result !== '';
					});

			$stock = collect( $data )->map(function ($item, $key) {
						return [ strtotime($item->data_date) * 1000, floatval($item->open), floatval($item->highest), floatval($item->lowest), floatval($item->close) ];
					})->values()->toArray();

			$result_data = collect( $data )->map(function ($item, $key) {
						return [ strtotime($item->data_date) * 1000, floatval($item->result) ];
					})->values()->toArray();

			$result["data"] = [
				"stock" 	=> $stock,
				"result" 	=> $result_data,
				"data"		=> Stock_logic::get_stock( $code )
			];

		} 
		catch (\Exception $e) 
		{
			
			$result = [
				"error" => true,
				"msg" 	=> $e->getMessage()
			];

		}

		return $result;

	}


	// 		自動計算買賣壓力

	public static function count_data_logic( $code )
	{

		$_this = new self();

		$result = false;

		if ( !empty($code) && is_int($code) ) 
		{

			// 		將檔案數據轉存資料庫

			$_this->create_data( $code );

			// 		計算收盤成交價差

			$_this->count_spread( $code );

			// 		計算買盤1

			$_this->count_buy1( $code );

			// 		計算賣盤1

			$_this->count_sell1( $code );

			// 		計算買盤2

			$_this->count_buy2( $code );

			// 		計算賣盤2

			$_this->count_sell2( $code );

			// 		計算漲幅總和、跌幅總和、買盤力道張數、賣盤力道張數

			$_this->count_pro_data( $code );

			// 		計算20天總買盤、20天總賣盤、買賣壓力道比例

			$_this->count_20days_data_and_result( $code );

			$result = true;
			
		}

		return $result;

	}


}










