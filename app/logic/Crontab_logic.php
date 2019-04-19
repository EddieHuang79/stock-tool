<?php

namespace App\logic;

use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\Storage;
use File;
use App\model\SellBuyPercent;
use App\Traits\SchemaFunc;
use App\Traits\stockFileLib;
use App\Traits\formatLib;

class Crontab_logic
{

	use SchemaFunc, stockFileLib, formatLib;


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

		return 'https://www.tpex.org.tw/web/stock/aftertrading/daily_trading_info/st43_download.php?l=zh-tw&d=' . $date . '&stkno=' . $code . '&s=[0,asc,0]';

	}


	// 	多筆cron的分批執行

	private function get_delay_config( $type )
	{

		$result = [
			"sleep_second" 	=> 0,
			"code_start" 	=> 0,
			"code_end" 		=> 0
		];

		switch ( $type ) 
		{

			case 1:

				$result = [
					"sleep_second" 	=> 0,
					"code_start" 	=> 0,
					"code_end" 		=> 4000
				];

				break;
			
			case 2:

				$result = [
					"sleep_second" 	=> 5,
					"code_start" 	=> 4001,
					"code_end" 		=> 5000
				];

				break;

			case 3:

				$result = [
					"sleep_second" 	=> 10,
					"code_start" 	=> 5001,
					"code_end" 		=> 6000
				];

				break;

			case 4:

				$result = [
					"sleep_second" 	=> 15,
					"code_start" 	=> 6001,
					"code_end" 		=> 6600
				];

				break;

			case 5:

				$result = [
					"sleep_second" 	=> 20,
					"code_start" 	=> 6601,
					"code_end" 		=> 8000
				];

				break;

			case 6:

				$result = [
					"sleep_second" 	=> 25,
					"code_start" 	=> 8001,
					"code_end" 		=> 9000
				];

				break;

			case 7:

				$result = [
					"sleep_second" 	=> 30,
					"code_start" 	=> 9001,
					"code_end" 		=> 999999
				];

				break;

		}

		return $result;

	}


	// 		取得股票基本五項資料轉存到文字檔
	/*

			開盤: open
			收盤: close
			最高: hightest
			最低: lowest
			成交量: trading_volume

	*/

	public static function get_stock_file( $code, $start, $end)
	{

		$_this = new self();

		try 
		{

			$end = mktime( 0, 0, 0, date('m', strtotime($end)), 1, date('Y', strtotime($end)) );

			$start = mktime( 0, 0, 0, date('m', strtotime($start)), 1, date('Y', strtotime($start)) );

			$now = $start;

			$i = 0;

			while ( $now <= $end ) 
			{

				$date = date("Ymd",  $now );

				$stock_data = Stock_logic::get_stock( $code );

				$url = $stock_data->type === 1 ? $_this->get_TWSE_listed_url( $date, $code ) : $_this->get_TPEx_listed_url( $date, $code ) ;

				$data = Curl::to( $url )->get();

				$_this->saveStockFile( $data, $date, $code, $stock_data->type );

				$i = $i + 1;

				$now = mktime( 0, 0, 0, (int)date('m', $start) + $i, 1, date('Y', $start) );

			}


		} 
		catch (\Exception $e) 
		{

			$_this->set_error_msg( $e, $position = 'get_stock_file' );

		}

		return true;

	}


	// 		Cron Job 自動取得所有股票資料
	/*

			區間: 近3年
			每次: 1份檔案(避免鎖IP)
			type: 撈資料的區間

	*/

	public static function auto_get_data( $type = 0 )
	{

		$_this = new self();

		$config = $_this->get_delay_config( $type );

		sleep($config["sleep_second"]);

		$limit = 1;

		$start = mktime( 0, 0, 0, 1, 1, date("Y") - 3 );

		// 取得所有的股票檔案

		$files = $_this->get_dir_files();

		// 存在的檔案

		$exist_file = collect( $files )->filter(function( $value, $key ) {
			return strpos( $value, date("Ym") ) !== false;
		})->map(function( $value, $key ) {
			$tmp = explode("/", $value);
			return intval($tmp[1]);
		})->values()->toArray();

		// 股票資料

		$list = Stock_logic::get_stock_option();

		$list = collect( $list["data"] )->pluck( 'value' )->filter( function( $value, $key ) use($exist_file, $config) {
			return !in_array( intval($value), $exist_file) && $value >= $config["code_start"] && $value <= $config["code_end"] ;
		} )->sort()->values()->toArray();

		Record_logic::write_operate_log( $action = 'auto_get_data type' . $type, $content = 'in process' );

		foreach ($list as $code) 
		{

			$i = 0;

			while ( $limit > 0 ) 
			{

				// 已存在的檔案

				$exists_stock_file = $_this->get_exist_data( $code );

				$loop_date = date( "Ymd", mktime( 0, 0, 0, (int)date('m', $start) + $i, 1, date('Y', $start) ) );

				$i++;

				if ( strtotime($loop_date) > time() ) 
				{
					
					break;
					
				}

				if ( !in_array($loop_date, $exists_stock_file) ) 
				{

					$_this->get_stock_file( $code, $loop_date, $loop_date );

					$limit--;
					
				}
				else
				{

					continue;

				}

				if ( $limit <= 0 || in_array( date("Ymd"), $exists_stock_file) ) 
				{

					break 2;
					
				}

			}

		}

		return true;

	}


	// 		刪除空白檔案

	public static function delete_empty_file()
	{

		$_this = new self();

		$files = $_this->get_empty_file();

		foreach ($files as $fileName) 
		{

			Storage::delete( $fileName );

		}
	
		return true;

	}


	// 		轉存基本股價資料
	/*
		
			無法取得當月資料

	*/

	public static function auto_save_file_to_db()
	{

		$_this = new self();

		// 取得已轉入資料庫內的資料

		$stock_data = Stock_logic::get_all_stock_data();

		// 依照現有的CSV檢查資料是否存在於資料庫

		$files = $_this->get_dir_files();

		$data = collect( $files )->filter(function( $item, $key ) use( $stock_data ) {
			$tmp = explode("/", $item);
			$code = isset($tmp[1]) ? intval($tmp[1]) : '';
			$date = isset($tmp[2]) ? str_replace(".csv", "", $tmp[2]) : '';
			$stock_data[$code] = isset($stock_data[$code]) ? $stock_data[$code] : [] ;
			$date = date("Ym", strtotime($date));
			return !in_array($date, $stock_data[$code]) && $code > 0 ;
		})->mapToGroups(function ($item, $key) {
			$tmp = explode("/", $item);
			$code = isset($tmp[1]) ? intval($tmp[1]) : '';
			return [$code => $item];
		})->toArray();

		Record_logic::write_operate_log( $action = 'auto_save_file_to_db', $content = 'in process' );

		// 股票基本資料

		$stock_info = Stock_logic::get_all_stock_info();

		foreach ($data as $code => $row) 
		{

			foreach ($row as $fileName) 
			{

				if ( Storage::size($fileName) < 1 ) 
				{
					
					continue;
					
				}

				$file_data = $_this->stock_data_to_array( $fileName );

				$stock_data = isset($stock_info[$code]) ? $stock_info[$code] : [];

				$insert_data = collect( $file_data )->map(function( $item, $key ) use($stock_data) {
					return [
						"stock_id" 				=> $stock_data->id,
						"data_date" 			=> date("Y-m-d", strtotime($item["date"])),
						"volume" 				=> (int)$stock_data->type === 1 ? $item["volume"] / 1000 : $item["volume"],
						"open" 					=> $item["open"],
						"highest" 				=> $item["highest"],
						"lowest" 				=> $item["lowest"],
						"close" 				=> $item["close"],
						"created_at" 			=> date("Y-m-d H:i:s"),
						"updated_at" 			=> date("Y-m-d H:i:s"),
					];
				})->toArray();

				if ( count($insert_data) > 1 ) 
				{

					Stock_logic::add_stock_data( $insert_data );
					
				}

			}

		}

		return true;	

	}


	// 		轉存當月基本股價資料
	/*
		
			無法取得當月資料

	*/

	public static function auto_save_this_month_file_to_db()
	{

		$_this = new self();

		// 取得已轉入資料庫內的資料

		$stock_data = Stock_logic::get_all_stock_data( $type = 2 );

		// 取得所有股票當月的檔案

		$files = $_this->get_dir_files();

		$data = collect( $files )->filter(function( $item, $key ) {
			$tmp = explode("/", $item);
			$code = isset($tmp[1]) ? intval($tmp[1]) : '';
			$date = isset($tmp[2]) ? str_replace(".csv", "", $tmp[2]) : '';
			$date = date("Ym", strtotime($date));
			return $date === date("Ym")  ;
		})->mapWithKeys(function ($item, $key) {
			$tmp = explode("/", $item);
			$code = isset($tmp[1]) ? intval($tmp[1]) : '';
			return [$code => $item];
		})->toArray();

		Record_logic::write_operate_log( $action = 'auto_save_this_month_file_to_db', $content = 'in process' );

		// 股票基本資料

		$stock_info = Stock_logic::get_all_stock_info();

		foreach ($data as $code => $fileName) 
		{

			if ( Storage::size($fileName) < 1 ) 
			{
				
				continue;
				
			}

			$file_data = $_this->stock_data_to_array( $fileName );

			$stock_info_array = isset($stock_info[$code]) ? $stock_info[$code] : [];

			$stock_data_array = isset($stock_data[$code]) ? $stock_data[$code] : [];

			$insert_data = collect( $file_data )->filter(function( $item, $key ) use($stock_data_array) {
								return !in_array( date("Ymd", strtotime($item["date"])), $stock_data_array )  ;
							})->map(function( $item, $key ) use($stock_info_array) {
								return [
									"stock_id" 				=> $stock_info_array->id,
									"data_date" 			=> date("Y-m-d", strtotime($item["date"])),
									"volume" 				=> (int)$stock_info_array->type === 1 ? $item["volume"] / 1000 : $item["volume"],
									"open" 					=> $item["open"],
									"highest" 				=> $item["highest"],
									"lowest" 				=> $item["lowest"],
									"close" 				=> $item["close"],
									"created_at" 			=> date("Y-m-d H:i:s"),
									"updated_at" 			=> date("Y-m-d H:i:s"),
								];
							})->toArray();

			if ( count($insert_data) > 1 ) 
			{

				Stock_logic::add_stock_data( $insert_data );
				
			}

		}

		return true;	

	}


	// 		自動計算買賣壓力
	/*
	
			沒資料的情況下，最多一次執行兩隻股票

	*/

	public static function auto_count_SellBuyPercent()
	{

		$limit = 1000;

		Record_logic::write_operate_log( $action = 'auto_count_SellBuyPercent', $content = 'in process' );

		// 取得要計算的資料量

		$data = SellBuyPercent_logic::get_all_stock_count_data_num();

		foreach ($data as $code => $value) 
		{

			$limit -= $value;

			SellBuyPercent_logic::count_data_logic( (int)$code );

			if ( $limit < 1 ) 
			{

				break;
				
			}

		}

	}



	// 		自動計算各項技術指標

	public static function auto_count_technical_analysis( $type )
	{

		$result = false;

		if ( !empty($type) && is_int($type) ) 
		{

			Record_logic::write_operate_log( $action = 'auto_count_technical_analysis', $content = 'in process' );

			$limit = 1000;

			// 取得已存在的stock_data_id

			$data = TechnicalAnalysis_logic::get_count_data( $type );

			$exist_stock_data_id = collect( $data )->mapToGroups( function ( $item, $key ) {
				return [ $item->code => $item->stock_data_id ];
			} )->toArray();

			// 取得所有的 stock_data_id

			$all_stock_data_id = Stock_logic::get_all_stock_data_id();

			// 計算數量

			$data_count = collect( $all_stock_data_id )->filter( function( $item, $code ) use($exist_stock_data_id) {
				$exist_count = isset($exist_stock_data_id[$code]) ? count($exist_stock_data_id[$code]) : 0 ;
				return $exist_count < 1 || count($item)/2 > $exist_count ;
			} )->mapWithKeys(function( $item, $code ) {
				return [$code => count($item)];
			})->toArray();

			foreach ($data_count as $code => $cnt) 
			{

				$limit -= $cnt;

				switch ( $type ) 
				{

					// RSV

					case 1:

						RSV_logic::count_data( $code );
					
						break;
					
					// K & D

					case 2:
					case 3:

						KD_logic::count_data( $code );

						break;

					// RSI

					case 4:
					case 5:

						RSI_logic::count_data( $code );
					
						break;

					// MACD

					case 6:
					case 7:
					case 8:

						MACD_logic::count_data( $code );
					
						break;

				}

				if ( $limit < 1 ) 
				{

					break;
					
				}

			}

			$result = true;
			
		}

		return $result;

	}


	/*

		透過通訊軟體自動回報選股條件
		條件 
		5個工作天內KD金叉(簡單做，日期-7)
		K || D < 20
		股價介於 20 - 80塊 之間
		5日總成交量 > 2500

	*/

	public static function daily_info( $type, $notice_type = 1 )
	{

		$_this = new self();

		$start = date("Y-m-d", mktime( 0, 0, 0, date("m"), date("d") - 7, date("Y") ));

		$end = date("Y-m-d");

		$data = Strategy_logic::get_strategy_data( $type, $start, $end );

		// KD 條件

		$KD_cross = collect( $data["KD_data"] )->filter(function( $item, $code ){
			return !empty($item["gold_cross"]); 
		})->filter(function( $item, $code ){
			$last = end($item["gold_cross"]);
			return $last["value1"] < 20 || $last["value2"] < 20 ; 
		})->mapWithKeys(function( $item, $code ){
			return [$code => end($item["gold_cross"])];
		});

		// 日常條件

		$daily_data = collect( $data["daily_data"] )->filter(function($item, $code) use($KD_cross) {
			$total = isset($item[0]) ? (int)$item[0]->volume : 0 ;
			$total += isset($item[1]) ? (int)$item[1]->volume : 0 ;
			$total += isset($item[2]) ? (int)$item[2]->volume : 0 ;
			$total += isset($item[3]) ? (int)$item[3]->volume : 0 ;
			$total += isset($item[4]) ? (int)$item[4]->volume : 0 ;
			return $item[0]->close >= 20 && $item[0]->close <= 80 && $total >= 2500 && isset($KD_cross[$code]) ; 
		})->toArray();

		// 通知訊息

		$notice_msg = '';

		foreach ($daily_data as $code => $item) 
		{

			$notice_msg .= $_this->notice_format( $msg = '-----' ) ;
			$notice_msg .= $_this->notice_format( $msg = '股票代號: ' . $code ) ;

			// 資料

			foreach ($item as $row) 
			{

				$notice_msg .= $_this->notice_format( $msg = '---' ) ;
				$notice_msg .= $_this->notice_format( $msg = '[日期]' . $row->data_date ) ;
				$notice_msg .= $_this->notice_format( $msg = '[成交量]' . $row->volume ) ;
				$notice_msg .= $_this->notice_format( $msg = '[開盤]' . $row->open ) ;
				$notice_msg .= $_this->notice_format( $msg = '[最高]' . $row->highest ) ;
				$notice_msg .= $_this->notice_format( $msg = '[最低]' . $row->lowest ) ;
				$notice_msg .= $_this->notice_format( $msg = '[收盤]' . $row->close ) ;
				$notice_msg .= $_this->notice_format( $msg = '---' ) ;

			}

			// KD

			$notice_msg .= $_this->notice_format( $msg = '[KD交叉日期]' . $KD_cross[$code]["date"] ) ;
			$notice_msg .= $_this->notice_format( $msg = '[K值]' . $KD_cross[$code]["value1"] ) ;
			$notice_msg .= $_this->notice_format( $msg = '[D值]' . $KD_cross[$code]["value2"] ) ;

			$notice_msg .=  $_this->notice_format( $msg = '-----' ) ;

		}

		$notice_msg = !empty($notice_msg) ? $notice_msg : '[' . date("Y-m-d H:i:s") . ']無相關資料';

		// 通知

		$notice = new Notice_logic();

		$notice->noticeUser( $type = 2, $notice_msg );

	}


	// 	更新每日各股資訊，取完檔案立刻寫入資料庫，每一次限制100筆，每筆資料取的區間為5秒
	// 	每寫入一次就更新redis值 [code => date]

	public static function update_daily_data()
	{

		$_this = new self();

		$date = date("Y-m-d");

		// 待更新的股票資料

		$list = Stock_logic::get_all_stock_update_date();

		$list = collect( $list )->slice(0, 50)->toArray();

		// 取得股票類型

		$code_type_mapping = Stock_logic::get_stock_type();

		foreach ($list as $code => $date) 
		{

			$date = date("Ym01");

			$type = isset($code_type_mapping[$code]) ? $code_type_mapping[$code] : 1 ;

			$url = $type === 1 ? $_this->get_TWSE_listed_url( $date, $code ) : $_this->get_TPEx_listed_url( $date, $code ) ;

			$data = Curl::to( $url )->get();

			$_this->saveStockFile( $data, $date, $code, $type );

			Record_logic::write_operate_log( $action = 'update_daily_data', $content = $code );

			sleep(2);

		}

		return true;

	}


	public static function test()
	{

		$_this = new self();

		dd(1);

	}



}
