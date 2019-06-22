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

    public function auto_count_SellBuyPercent()
    {

        //  工作日期

        $last_working_date = date("Y-m-d", strtotime("-0 days"));

        // 取得已存在的資料 帶入目標工作日

        $data = SellBuyPercent_logic::getInstance()->get_last_stock_code( $last_working_date );

        $last_code = !empty($data->code) ? $data->code : 0 ;

        $content = (int)$last_code < 9820 ? 'in process' : 'no data';

        Record_logic::getInstance()->write_operate_log( $action = 'auto_count_SellBuyPercent', $content );

        if ( (int)$last_code >= 9820 )
            return true;

        // 取得所有股票

        //  股價為 -- 的項目會計算上有誤差，撈出來排除掉

        $Stock = Stock_logic::getInstance();

        $not_read = $Stock->get_stock_by_none_price()->pluck("code")->toArray();

        $Stock->get_all_stock_info()->filter(function ($item) use($last_code, $not_read) {
            return $item->code > $last_code && !in_array( $item->code, $not_read ) ;
        })->forPage(0, 30)->map(function ($item) {

            SellBuyPercent_logic::getInstance()->count_data_logic( $item->code );

        });

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}
