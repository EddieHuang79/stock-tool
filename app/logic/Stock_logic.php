<?php

namespace App\logic;

use App\model\Stock;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Traits\SchemaFunc;
use App\Traits\stockFileLib;
use Hash;
use Uuid;
use Illuminate\Support\Facades\Storage;

class Stock_logic extends Basetool
{

	use SchemaFunc, stockFileLib;

	protected $txt = array();

	public function __construct()
	{

		$this->txt = __('user');

	}

	// 	取得股票列表

	public static function get_list()
	{

		$_this = new self();

		$result = [
			"error"	=>	false,
			"data"	=>	[]
		];

		$first_data_time = $_this->map_with_key( SellBuyPercent_logic::get_first_data_time(), $key1 = 'stock_id', $key2 = 'data_date' );

		$last_update_time = $_this->map_with_key( SellBuyPercent_logic::get_last_update_time(), $key1 = 'stock_id', $key2 = 'data_date' );

		$result["data"] = collect( Stock::get_list() )->map(function($item, $key) use($first_data_time, $last_update_time) {
			$item->first_data = isset($first_data_time[$item->id]) ? $first_data_time[$item->id] : '尚無資料' ;
			$item->last_updated = isset($last_update_time[$item->id]) ? $last_update_time[$item->id] : '尚無資料' ;
			return $item;
		})->values()->toArray() ;

		return $result;

	}

	// 	取得資料

	public static function get_stock( $code )
	{

		return Stock::get_stock( $code );

	}


	// 		取得股票清單

	public static function get_stock_option()
	{

		$_this = new self();

		$result = [
			"error" => false,
			"data" 	=> []
		];

		$data = Stock::get_stock_list();

		$result["data"] = collect( $data )->map(function($item, $key){
			return ["text" => $item->code . ' - ' . $item->name, "value" => $item->code];
		})->values()->toArray();

		return $result;

	}


	// 		寫入基本資料

	public static function add_stock_info( $data )
	{

		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{

			Stock::add_stock_info( $data );
			
			$result = true;

		}

		return $result;

	}


	// 		寫入5項數據

	public static function add_stock_data( $data )
	{

		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{

			$result = Stock::add_stock_data( $data );

		}

		return $result;

	}


	// 		取得股票基本資料

	public static function get_all_stock_info()
	{

		$data = Stock::get_all_stock_info();

		$result = collect( $data )->mapWithKeys(function ($item, $key) {
			return [$item->code => $item];
		})->toArray();

		return $result;

	}


	// 		取得5項數據

	public static function get_stock_data( $id )
	{

		$result = false;

		if ( !empty($id) && is_int($id) ) 
		{

			$result = Stock::get_stock_data( $id );

		}

		return $result;

	}


	// 		取得已轉入資料庫內的資料
	// 		type: 1 - 撈全部，type:2 - 撈當月

	public static function get_all_stock_data( $type = 1 )
	{

		$data = Stock::get_all_stock_data( $type );

		$result = collect( $data )->mapToGroups(function ($item, $key) use($type) {
			$value = $type === 1 ? date("Ym", strtotime($item->data_date)) : date("Ymd", strtotime($item->data_date)) ;
			return [$item->code => $value];
		})->toArray();

		return $result;

	}


	// 		取得已轉入資料庫內的資料

	public static function get_all_stock_data_id()
	{

		$data = Stock::get_all_stock_data_id();

		$result = collect( $data )->mapToGroups(function ($item, $key) {
			return [$item->code => $item->stock_data_id];
		})->toArray();

		return $result;

	}


	// 		取得5項數據

	public static function get_stock_data_by_date_range( $start, $end )
	{

		$result = false;

		if ( !empty($start) && is_string($start) && !empty($end) && is_string($end) ) 
		{

			$data = Stock::get_stock_data_by_date_range( $start, $end );

			$result = $data->mapToGroups(function( $item, $key ) {
				return [ $item->code => $item ];
			})->toArray();

		}

		return $result;

	}


	// 		從檔案取得所有已更新到本月的股票的最新更新日期

	public static function get_all_stock_update_date()
	{

		$_this = new self();

		// 取得所有檔案

		$files = $_this->get_dir_files();

		// 產生待更新清單

		$result = collect( $files )->filter(function( $value, $key ) {
			return strpos( $value, date("Ym") ) !== false;
		})->mapWithKeys( function( $fileName, $key ) use( $_this ) {
			$tmp = explode("/", $fileName);
			$code = isset($tmp[1]) ? intval($tmp[1]) : '' ;
			$data = $_this->stock_data_to_array( $fileName );
			$last = end($data);
			$last_date = isset($last["date"]) ? $last["date"] : '' ;
			return [ $code => $last_date ];
		})->filter(function( $date, $code ) {
			return $date !== date("Y-m-d");
		})->toArray();

		return $result;

	}


	// 		取得股票類型(上市、上櫃)

	public static function get_stock_type()
	{

		$data = Stock::get_stock_list();

		$result = collect( $data )->mapWithKeys(function ($item, $key) {
			return [$item->code => $item->type];
		})->toArray();

		return $result;		

	}

}
