<?php

namespace App\logic;

use Illuminate\Support\Facades\DB;

class DataDivide_logic
{
    private $start_date = '2016-01-04';

    public function divide_stock_data()
    {
        $startTime = microtime(true);

        $date = Redis_tool::getInstance()->getDivideStockDataKey();

        $startDate = !empty($date) ? $date : $this->start_date;

        // $endDate = Holiday_logic::getInstance()->get_work_date( $after_days = 1, $startDate, $type = 2 );
        $endDate = $startDate;

        $endDate = date('Y', strtotime($startDate)) !== date('Y', strtotime($endDate)) ? $startDate : $endDate;

        $year = date('Y', strtotime($startDate));

        if ((int) $year >= date('Y')) {
            Record_logic::getInstance()->write_operate_log($action = 'divide_stock_data', 'done');

            return true;
        }

        $data = DB::table('stock_data')->whereBetween('data_date', [$startDate, $endDate])->get();

        DB::table('stock_data_'.$year)->insert($data->map(function ($item) {
            $item = get_object_vars($item);
            unset($item['id']);

            return $item;
        })->toArray());

        Redis_tool::getInstance()->setDivideStockDataKey(Holiday_logic::getInstance()->get_work_date($after_days = 1, $endDate, $type = 2));

        $totalTime = microtime(true) - $startTime;

        Record_logic::getInstance()->write_operate_log($action = 'divide_stock_data', '['.$startDate.','.$endDate.'], execute time: '.$totalTime);
    }

    public function divide_technical_data()
    {
        $startTime = microtime(true);

        $date = Redis_tool::getInstance()->getDivideTechKey();

        $startDate = !empty($date) ? $date : $this->start_date;

        // $endDate = Holiday_logic::getInstance()->get_work_date( $after_days = 1, $startDate, $type = 2 );
        $endDate = $startDate;

        $endDate = date('Y', strtotime($startDate)) !== date('Y', strtotime($endDate)) ? $startDate : $endDate;

        $year = date('Y', strtotime($startDate));

        if ((int) $year >= date('Y')) {
            Record_logic::getInstance()->write_operate_log($action = 'divide_technical_data', 'done');

            return true;
        }

        $data = DB::table('technical_analysis')->whereBetween('data_date', [$startDate, $endDate])->get();

        DB::table('technical_analysis_'.$year)->insert($data->map(function ($item) {
            $item = get_object_vars($item);
            unset($item['id']);

            return $item;
        })->toArray());

        Redis_tool::getInstance()->setDivideTechKey(Holiday_logic::getInstance()->get_work_date($after_days = 1, $endDate, $type = 2));

        $totalTime = microtime(true) - $startTime;

        Record_logic::getInstance()->write_operate_log($action = 'divide_technical_data', '['.$startDate.','.$endDate.'], execute time: '.$totalTime);
    }

    public function divide_sellBuyPercent_data()
    {
        $startTime = microtime(true);

        $date = Redis_tool::getInstance()->getDivideSellBuyKey();

        $startDate = !empty($date) ? $date : $this->start_date;

        // $endDate = Holiday_logic::getInstance()->get_work_date( $after_days = 1, $startDate, $type = 2 );
        $endDate = $startDate;

        $endDate = date('Y', strtotime($startDate)) !== date('Y', strtotime($endDate)) ? $startDate : $endDate;

        $year = date('Y', strtotime($startDate));

        if ((int) $year >= date('Y')) {
            Record_logic::getInstance()->write_operate_log($action = 'divide_sellBuyPercent_data', 'done');

            return true;
        }

        $data = DB::table('sell_buy_percent')->whereBetween('data_date', [$startDate, $endDate])->get();

        DB::table('sell_buy_percent_'.$year)->insert($data->map(function ($item) {
            $item = get_object_vars($item);
            unset($item['id']);

            return $item;
        })->toArray());

        Redis_tool::getInstance()->setDivideSellBuyKey(Holiday_logic::getInstance()->get_work_date($after_days = 1, $endDate, $type = 2));

        $totalTime = microtime(true) - $startTime;

        Record_logic::getInstance()->write_operate_log($action = 'divide_sellBuyPercent_data', '['.$startDate.','.$endDate.'], execute time: '.$totalTime);
    }

    public static function getInstance()
    {
        return new self();
    }
}
