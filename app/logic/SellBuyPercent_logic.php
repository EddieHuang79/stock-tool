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

	protected $code = '4952';


	/*  curl config  */

	// 		上市網址

	private function get_TWSE_listed_url( $date, $code )
	{

		return 'http://www.twse.com.tw/exchangeReport/STOCK_DAY?response=csv&date=' . $date . '&stockNo=' . $code;

	}


	// 		上櫃網址
	//		$response = Curl::to( $url )->withResponseHeaders()->returnResponseObject()->get(); 破解

	private function get_TPEx_listed_url( $date, $code )
	{

		$_this = new self();

		$date = $_this->year_change( $date );

		return 'http://www.tpex.org.tw/web/stock/aftertrading/daily_trading_info/st43_download.php?l=zh-tw&d=' . $date . '&stkno=' . $code . '&s=[0,asc,0]';

	}


	/*  資料邏輯  */

	// 		民國日期轉西元日期

	protected function date_transformat( $date )
	{

		$result = '';

		if ( !empty($date) && is_string($date) ) 
		{
	
			$tmp = explode("/", $date);
	
			$tmp[0] = 1911 + (int)$tmp[0];
	
			$result = implode("-", $tmp);

		}

		return $result;

	}


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


	// 		取得股票清單

	public static function get_stock_list()
	{

		$_this = new self();

		return SellBuyPercent::get_stock_list();

	}


	// 		取得統計數據

	protected function get_statistics( $code )
	{

		$result = [];

		if ( !empty($code) ) 
		{

			$result = SellBuyPercent::get_statistics( $code );
			
		}

		return $result;

	}


	// 		寫入資料

	public static function add_stock_data( $data )
	{

	     $result = false;

	     if ( !empty($data) && is_array($data) ) 
	     {

	        $result = SellBuyPercent::add_stock_data( $data );

	     }

	     return $result ;

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


	/* 資料流程 */




	// 		取得資料

	public static function get_stock_data( $code, $start, $end)
	{

		$_this = new self();

		try 
		{

			// 固定抓取過去半年的資料

			// $get_month = 5;
			$end = mktime( 0, 0, 0, date('m', strtotime($end)), 1, date('Y', strtotime($end)) );

			$start = mktime( 0, 0, 0, date('m', strtotime($start)), 1, date('Y', strtotime($start)) );

			$now = $start;

			$i = 0;

			while ( $now <= $end ) 
			{

				// $time = mktime( 0, 0, 0, date('m') - $i, 1, date("Y") );

				$date = date("Ymd",  $now );

				$file_path = $_this->parents_dir . '/' . $code;

				Storage::makeDirectory( $file_path );

				$file_name = $file_path . '/' . $date . '.csv';

				$data = Stock_logic::get_stock( $code );

				// 偵測檔案是否存在，存在則跳過，但若是本月要覆蓋

				if ( Storage::exists( $file_name ) === true && date("Ym") !== date("Ym", $now) ) 
				{
					
					$now = mktime( 0, 0, 0, (int)date('m', $start) + $i, 1, date('Y', $start) );

					$i = $i + 1;

					continue;

				}

				$url = $data->type === 1 ? $_this->get_TWSE_listed_url( $date, $code ) : $_this->get_TPEx_listed_url( $date, $code ) ;

				$response = Curl::to( $url )->get();

				$tmp = explode("\r\n", $response);
			
				$cnt = count($tmp);

				$data = $data->type === 1 ? array_slice( $tmp, 2, $cnt - 8  ) : array_slice( $tmp, 5, $cnt - 6  ) ; 

				Storage::put( $file_name , implode("\r\n", $data) );

				$now = mktime( 0, 0, 0, (int)date('m', $start) + $i, 1, date('Y', $start) );

				$i = $i + 1;

			}


		} 
		catch (\Exception $e) 
		{
	
			$_this->set_error_msg( $e, $position = 'get_stock_data' );

		}

		return true;

	}


	// 		將檔案數據轉存資料庫

	public static function file_to_db( $code )
	{

		$_this = new self();

		$file_path = $_this->parents_dir . '/' . $code;		

		$files = Storage::allFiles( $file_path );

		$exists_data = $_this->check_data_repeat( $code );

		$stock_data = Stock_logic::get_stock( $code );

		$stock_data_id = $stock_data->id;

		foreach ($files as $row) 
		{

			$file_data = Storage::get( $row );

			$data = explode("\r\n", $file_data);

			foreach ($data as $item) 
			{

				$item_data = explode('","', $item);

				// 過濾資料

				$item_data = collect( $item_data )->map(function ($item, $key) {
					return str_replace('"', '', str_replace(',', '', $item));
				})->toArray();

				// 日期

				$date = $_this->date_transformat( $item_data[0] );

				// 判斷重複

				if ( in_array($date, $exists_data) ) 
				{
					
					continue;
					
				}

				// 成交量

				$weak_market = $stock_data->type === 1 ? (int)$item_data[1] / 1000 : (int)$item_data;

				// 開盤

				$begin = $item_data[3];
			
				// 最高

				$highest = $item_data[4];
				
				// 最低

				$lowest = $item_data[5];
				
				// 收盤

				$finish = $item_data[6];

				// 寫入資料

				$insert_data = [
					"stock_data_id" 		=> $stock_data_id,
					"data_date" 			=> $date,
					"weak_market" 			=> (int)$weak_market,
					"begin" 				=> $begin,
					"highest" 				=> $highest,
					"lowest" 				=> $lowest,
					"finish" 				=> $finish,
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

		}

		return true;

	}


	// 		計算收盤成交價差
	// 		公式: 當日收盤 - 前日收盤 

	public static function count_spread( $code )
	{

		$_this = new self();

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

			$last_finish = floatval($yesterday_data->finish);

			// 今日收盤

			$today_finish = floatval($row->finish);

			$spread = round( $today_finish - $last_finish, 2 );

			// 更新資料

			$_this->edit_sell_buy_percent_data( ["spread" => $spread, "updated_at" => date("Y-m-d H:i:s")], $row->id );

		}

		return true;

	}


	// 		計算買盤1
	// 		公式:
	/*
			若 今日收盤價 > 昨日收盤價
			買盤1 = 今日開盤價 - 昨日收盤價
			若 今日收盤價 <= 昨日收盤價
			買盤1 = 今日最高價 - 今日開盤價
	*/

	public static function count_buy1( $code )
	{

		$_this = new self();

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

			$last_finish = floatval($yesterday_data->finish);

			// 今日收盤

			$today_finish = floatval($row->finish);

			$buy1 = $today_finish > $last_finish ? round( floatval($row->begin) - $last_finish, 2) : round( floatval($row->highest) - floatval($row->begin), 2 ) ;

			// 更新資料

			$_this->edit_sell_buy_percent_data( ["buy1" => $buy1, "updated_at" => date("Y-m-d H:i:s")], $row->id );

		}

		return true;

	}


	// 		計算賣盤1
	// 		公式:
	/*
			若 今日收盤價 > 昨日收盤價
			賣盤1 = 今日開盤價 - 今日最低價
			若 今日收盤價 <= 昨日收盤價
			賣盤1 = 昨日收盤價 - 今日開盤價
	*/

	public static function count_sell1( $code )
	{

		$_this = new self();

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

			$last_finish = floatval($yesterday_data->finish);

			// 今日收盤

			$today_finish = floatval($row->finish);

			$sell1 = $today_finish > $last_finish ? round( floatval($row->begin) - floatval($row->lowest), 2) : round( $last_finish - floatval($row->begin), 2 ) ;

			// 更新資料

			$_this->edit_sell_buy_percent_data( ["sell1" => $sell1, "updated_at" => date("Y-m-d H:i:s")], $row->id );

		}

		return true;

	}


	// 		計算買盤2
	// 		公式:
	/*
			若 今日收盤價 > 昨日收盤價
			買盤2 = 今日最高價 - 今日最低價
			若 今日收盤價 <= 昨日收盤價
			買盤2 = 今日收盤價 - 今日最低價
	*/

	public static function count_buy2( $code )
	{

		$_this = new self();

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

			$last_finish = floatval($yesterday_data->finish);

			// 今日收盤

			$today_finish = floatval($row->finish);

			$buy2 = $today_finish > $last_finish ? round( floatval($row->highest) - floatval($row->lowest), 2) : round( $today_finish - floatval($row->lowest), 2 ) ;


			// 更新資料
			$_this->edit_sell_buy_percent_data( ["buy2" => $buy2, "updated_at" => date("Y-m-d H:i:s")], $row->id );

		}

		return true;

	}


	// 		計算賣盤2
	// 		公式:
	/*
			若 今日收盤價 > 昨日收盤價
			賣盤2 = 今日最高價 - 今日收盤價
			若 今日收盤價 <= 昨日收盤價
			賣盤2 = 今日最高價 - 今日最低價
	*/

	public static function count_sell2( $code )
	{

		$_this = new self();

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

			$last_finish = floatval($yesterday_data->finish);

			// 今日收盤

			$today_finish = floatval($row->finish);

			$sell2 = $today_finish > $last_finish ? round( floatval($row->highest) - $today_finish, 2) : round( floatval($row->highest) - floatval($row->lowest), 2 ) ;

			// 更新資料

			$_this->edit_sell_buy_percent_data( ["sell2" => $sell2, "updated_at" => date("Y-m-d H:i:s")], $row->id );

		}

		return true;

	}


	// 		計算漲幅總和、跌幅總和、買盤力道張數、賣盤力道張數
	// 		公式:
	/*
			漲幅總和 = 買盤1+買盤2
			跌幅總和 = 賣盤1+賣盤2
			買盤力道張數 = 成交量 * ( 漲幅總和 / ( 漲幅總和+跌幅總和) )
			賣盤力道張數 = 成交量 * ( 跌幅總和 / ( 漲幅總和+跌幅總和) )
	*/

	public static function count_pro_data( $code )
	{

		$_this = new self();

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

			$rally_num1 = $sum > 0 ? round( $row->weak_market * ( $rally_total / ($rally_total + $tumbled_total ) ), 2 ) : 0;

			// 賣盤力道張數

			$tumbled_num1 = $sum > 0 ? round( $row->weak_market * ( $tumbled_total / ($rally_total + $tumbled_total ) ), 2 ) : 0;

			// 更新資料

			$_this->edit_sell_buy_percent_data( ["rally_total" => $rally_total, "tumbled_total" => $tumbled_total, "rally_num1" => $rally_num1, "tumbled_num1" => $tumbled_num1, "updated_at" => date("Y-m-d H:i:s")], $row->id );

		}

		return true;

	}


	// 		計算20天總買盤、20天總賣盤、買賣壓力道比例
	// 		公式:
	/*
			20天總買盤 = 買盤力道張數過去20天加總
			20天總賣盤 = 賣盤力道張數過去20天加總
			買賣壓力道比例 = 20天總賣盤/20天總買盤
	*/

	public static function count_20days_data_and_result( $code )
	{

		$_this = new self();

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

		return true;

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
						return [ strtotime($item->data_date) * 1000, floatval($item->begin), floatval($item->highest), floatval($item->lowest), floatval($item->finish) ];
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


	// 		取得股票清單

	public static function get_stock_list_logic()
	{

		$_this = new self();

		$result = [
			"error" => false,
			"data" 	=> []
		];

		$data = $_this->get_stock_list();

		$result["data"] = collect( $data )->map(function($item, $key){
			return ["text" => $item->code . ' - ' . $item->name, "value" => $item->code];
		})->values()->toArray();

		return $result;

	}

}










