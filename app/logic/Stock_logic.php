<?php

namespace App\logic;

use App\model\Stock;
use App\Traits\SchemaFunc;
use App\Traits\stockFileLib;

class Stock_logic
{

	use SchemaFunc, stockFileLib;

	private $stop_trade = [
		1213,
		1603,
		5259,
		1566,
	];

	private $default_start_trade_date = '2016-01-01';

	// 	取得股票列表

	public function get_list()
	{

		$result = [
			"error"	=>	false,
			"data"	=>	[]
		];

		$first_data_time = $this->map_with_key( SellBuyPercent_logic::getInstance()->get_first_data_time(), $key1 = 'stock_id', $key2 = 'data_date' );

		$last_update_time = $this->map_with_key( SellBuyPercent_logic::getInstance()->get_last_update_time(), $key1 = 'stock_id', $key2 = 'data_date' );

		$result["data"] = collect( Stock::getInstance()->get_list() )->map(function( $item ) use($first_data_time, $last_update_time) {
			$item->first_data = isset($first_data_time[$item->id]) ? $first_data_time[$item->id] : '尚無資料' ;
			$item->last_updated = isset($last_update_time[$item->id]) ? $last_update_time[$item->id] : '尚無資料' ;
			return $item;
		})->values()->toArray() ;

		return $result;

	}

	// 	取得資料

	public function get_stock( $code )
	{

		return Stock::getInstance()->get_stock( $code );

	}


	// 		取得股票清單

	public function get_stock_option()
	{

		$result = [
			"error" => false,
			"data" 	=> []
		];

		$data = Stock::getInstance()->get_stock_list();

		$result["data"] = collect( $data )->map(function( $item ){
			return ["text" => $item->code . ' - ' . $item->name, "value" => $item->code];
		})->values()->toArray();

		return $result;

	}


	// 		寫入5項數據

	public function add_stock_data( $data )
	{

		$result = false;

		if ( !empty($data) && is_array($data) )
		{

			$result = Stock::getInstance()->add_stock_data( $data );

		}

		return $result;

	}


	// 		取得股票基本資料

	public function get_all_stock_info()
	{

		return Stock::getInstance()->get_all_stock_info()->mapWithKeys(function ( $item ) {
            return [$item->code => $item];
        });

	}


	// 		取得5項數據

	public function get_stock_data( $id, $start = '', $end = '' )
	{

		$result = false;

		if ( !empty($id) && is_int($id) )
		{

			$result = Stock::getInstance()->get_stock_data( $id, $start, $end )->map( function( $item ) {
                $item->volume = intval($item->volume);
                $item->open = floatval($item->open);
                $item->close = floatval($item->close);
                $item->highest = floatval($item->highest);
                $item->lowest = floatval($item->lowest);
                return $item;
            } );

		}

		return $result;

	}


	// 		取得已轉入資料庫內的資料
	// 		type: 1 - 撈全部，type:2 - 撈當月

	public function get_all_stock_data( $type = 1, $sub_type = 1 )
	{

		$data = Stock::getInstance()->get_all_stock_data( $type, $sub_type );

		$result = collect( $data )->mapToGroups(function ( $item ) use($type) {
			$value = $type === 1 ? date("Ym", strtotime($item->data_date)) : date("Ymd", strtotime($item->data_date)) ;
			return [$item->code => $value];
		})->toArray();

		return $result;

	}


	// 		取得已轉入資料庫內的資料

	public function get_all_stock_data_id()
	{

		$data = Stock::getInstance()->get_all_stock_data_id();

		$result = collect( $data )->mapToGroups(function ( $item ) {
			return [$item->code => $item->stock_data_id];
		})->toArray();

		return $result;

	}


	// 		取得5項數據

	public function get_stock_data_by_date_range( $start, $end, $code = '' )
	{

		$result = false;

		if ( !empty($start) && is_string($start) && !empty($end) && is_string($end) )
		{

			$data = Stock::getInstance()->get_stock_data_by_date_range( $start, $end, $code );

			$result = $data->mapToGroups(function( $item ) {
                $item->open = floatval($item->open);
                $item->highest = floatval($item->highest);
                $item->lowest = floatval($item->lowest);
                $item->close = floatval($item->close);
				return [ $item->code => $item ];
			})->toArray();

		}

		return $result;

	}


	//      從檔案取得所有已更新到本月的股票的最新更新日期 >> 新版

    public function get_all_stock_update_date_new( $type = 1 )
    {

        $stop_trade = $this->stop_trade;

        // 取得指定股票區間的當月的檔案

        switch ( $type )
        {

            case 1:

                $sec = 0;

                $start = 1101;

                $end = 1569;

                break;

            case 2:

                $sec = 5;

                $start = 1580;

                $end = 2221;

                break;

            case 3:

                $sec = 10;

                $start = 2227;

                $end = 2492;

                break;


            case 4:

                $sec = 15;

                $start = 2493;

                $end = 3013;

                break;


            case 5:

                $sec = 20;

                $start = 3014;

                $end = 3339;

                break;

            case 6:

                $sec = 25;

                $start = 3346;

                $end = 3704;

                break;

            case 7:

                $sec = 30;

                $start = 3705;

                $end = 4747;

                break;

            case 8:

                $sec = 35;

                $start = 4754;

                $end = 5443;

                break;

            case 9:

                $sec = 40;

                $start = 5450;

                $end = 6187;

                break;

            case 10:

                $sec = 45;

                $start = 6188;

                $end = 6535;

                break;

            case 11:

                $sec = 50;

                $start = 6538;

                $end = 8404;

                break;

            case 12:

                $sec = 55;

                $start = 8406;

                $end = 912398;

                break;

        }

        $result = [
            "sec"           => $sec,
            "start"         => $start,
            "end"           => $end,
            "stop_trade"    => $stop_trade
        ];

        return $result;

    }



    // 		取得股票類型(上市、上櫃)

	public function get_stock_type()
	{

		$data = Stock::getInstance()->get_stock_list();

		$result = collect( $data )->mapWithKeys(function ( $item ) {
			return [$item->code => $item->type];
		})->toArray();

		return $result;

	}


	// 		取得開始交易日期

	public function get_start_trade_date( $stock_id )
	{

		$result = $this->default_start_trade_date;

		if ( !empty($stock_id) && is_int($stock_id) )
		{

			$date = Stock::getInstance()->get_start_trade_date( $stock_id )->data_date;

			$result = strtotime($result) >= strtotime($date) ? $result : $date ;

		}

		return $result;

	}


	//      取得不正常的股價

    public function get_stock_by_none_price()
    {

        return Stock::getInstance()->get_stock_by_none_price();

    }


    // 		取得等待更新的股票

    public function get_wait_to_update_stock( $start, $end, $except )
    {

        $result = $this->get_all_stock_info()->filter(function ($item, $key) use($start, $end, $except) {
            return $start <= $key && $key <= $end && !in_array($key, $except);
        })->map( function($item, $key) {
            return $key;
        })->slice(0, 1)->values()->toArray();

        return $result;

    }


    public function get_stock_id( $code = [] )
    {

        return Stock::getInstance()->get_stock_id( $code );

    }

    public function get_assign_code_stock_data( $stock_id = [] )
    {

        return Stock::getInstance()->get_assign_code_stock_data( $stock_id );

    }

    public static function getInstance()
    {

        return new self;

    }



}
