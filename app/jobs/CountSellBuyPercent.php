<?php

namespace App\jobs;

use App\logic\Record_logic;
use App\logic\SellBuyPercent_logic;
use App\logic\Stock_logic;
use App\logic\Holiday_logic;


class CountSellBuyPercent
{

    // 		自動計算買賣壓力
    /*

            沒資料的情況下，最多一次執行兩隻股票

    */

    public function auto_count_SellBuyPercent( $date = '' )
    {

        $start = microtime(true);

        //  工作日期

        $last_working_date = $date;

        // 取得已存在的資料 帶入目標工作日

        $count_done_stock_id = SellBuyPercent_logic::getInstance()->get_count_done_stock_id( $last_working_date )->pluck("stock_id");

        // 取今天轉好的stock_data

        $today_stock_id = SellBuyPercent_logic::getInstance()->get_today_exist_stock_data( $last_working_date );

        $content = $count_done_stock_id->count() < 1603 ? 'in process' : 'no data';

        Record_logic::getInstance()->write_operate_log( $action = 'auto_count_SellBuyPercent', $content );

        if ( $count_done_stock_id->count() >= 1603 )
            return true;

        // 取得所有股票

        //  股價為 -- 的項目會計算上有誤差，撈出來排除掉

        $Stock = Stock_logic::getInstance();

        $not_read = $Stock->get_stock_by_none_price()->pluck("id")->toArray();

        $main_data = $today_stock_id->filter(function ($item) use($count_done_stock_id, $not_read) {
            return !in_array( $item->id, $count_done_stock_id->toArray()) && !in_array( $item->id, $not_read ) ;
        })->forPage(0, 200);

        // 取得這次要用的股價資料

        $end_count_day = date("Y-m-d");
        $start_count_day = Holiday_logic::getInstance()->get_work_date( $before_days = 100, $end_count_day, $type = 1 );

        $statistics = $Stock->get_stock_data( $main_data->pluck("id")->toArray(), $start_count_day, $end_count_day );        

        $sellBuyPercentDate = SellBuyPercent_logic::getInstance()->get_data_assign_range( $main_data->pluck("id")->toArray(), $start_count_day, $end_count_day ); 

        $main_data->filter(function ($item) use($statistics, $sellBuyPercentDate) {
            return isset($statistics[$item->id]) && isset($sellBuyPercentDate[$item->id]);
        })->map(function ($item) use($statistics, $sellBuyPercentDate) {
            SellBuyPercent_logic::getInstance()->count_data_logic( $item, $statistics[$item->id], $sellBuyPercentDate[$item->id]->pluck('data_date')->toArray() );
        });

        $final = microtime(true) - $start;

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}
