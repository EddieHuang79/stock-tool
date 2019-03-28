<?php

namespace App\logic;

use App\model\Stock;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Traits\SchemaFunc;
use Hash;
use Uuid;

class Stock_logic extends Basetool
{

	use SchemaFunc;

	protected $txt = array();

	public function __construct()
	{

		$this->txt = __('user');

	}

	public static function get_list()
	{

		$_this = new self();

		$result = [
			"error"	=>	false,
			"data"	=>	[]
		];

		$first_data_time = $_this->map_with_key( SellBuyPercent_logic::get_first_data_time(), $key1 = 'stock_data_id', $key2 = 'data_date' );

		$last_update_time = $_this->map_with_key( SellBuyPercent_logic::get_last_update_time(), $key1 = 'stock_data_id', $key2 = 'data_date' );

		$result["data"] = collect( Stock::get_list() )->map(function($item, $key) use($first_data_time, $last_update_time) {
			$item->first_data = isset($first_data_time[$item->id]) ? $first_data_time[$item->id] : '尚無資料' ;
			$item->last_updated = isset($last_update_time[$item->id]) ? $last_update_time[$item->id] : '尚無資料' ;
			return $item;
		})->values()->toArray() ;

		return $result;

	}

	public static function get_stock( $code )
	{

		return Stock::get_stock( $code );

	}

}
