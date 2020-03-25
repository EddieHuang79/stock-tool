<?php

namespace App\jobs;

use App\logic\BollingerBands_logic;
use App\logic\Holiday_logic;
use App\logic\KD_logic;
use App\logic\MACD_logic;
use App\logic\Record_logic;
use App\logic\RSI_logic;
use App\logic\Stock_logic;
use App\logic\TechnicalAnalysis_logic;

class CountTechnicalAnalysis
{
    //      計算全部指標

    public function count_all()
    {
        // 找出要計算的前10支股票代號 50 秒

        $Tech = TechnicalAnalysis_logic::getInstance();

        $start = microtime(true);

        $stock_data = $Tech->get_stock_tech_update_date_v2();

        $content = $stock_data->count() > 0 ? 'in process' : 'no data';

        Record_logic::getInstance()->write_operate_log($action = 'auto_count_technical_analysis_all', $content);

        if ($stock_data->count() < 1) {
            return true;
        }

        $end_count_day = date('Y-m-d');
        $start_count_day = Holiday_logic::getInstance()->get_work_date($before_days = 100, $end_count_day, $type = 1);

        // 本次執行的所有股票id

        $stock_id = $stock_data->pluck('stock_id')->toArray();

        // 日期與id的對應

        $Tech_data = $Tech->get_data($stock_id, $start_count_day, $end_count_day);

        $stock_price_data = Stock_logic::getInstance()->get_stock_data($stock_id, $start_count_day, $end_count_day);

        $stock_data->filter(function ($item) use ($Tech_data, $stock_price_data) {
            return isset($Tech_data[$item->stock_id]) && isset($stock_price_data[$item->stock_id]);
        })->map(function ($item) use ($Tech_data, $stock_price_data, $Tech) {
            // 流水號

            $stock_id = $item->stock_id;

            $id_date_mapping = $Tech_data[$stock_id]->mapWithKeys(function ($item) {
                return [$item->data_date => $item->id];
            })->toArray();

            $all_data = [
                'KD' => KD_logic::getInstance()->return_data($Tech_data[$stock_id], $stock_price_data[$stock_id]),
                'RSI' => RSI_logic::getInstance()->return_data($Tech_data[$stock_id], $stock_price_data[$stock_id]),
                'MACD' => MACD_logic::getInstance()->return_data($Tech_data[$stock_id], $stock_price_data[$stock_id]),
                'Bollinger' => BollingerBands_logic::getInstance()->return_data($Tech_data[$stock_id], $stock_price_data[$stock_id]),
            ];

            $update_data = [];

            foreach ($all_data as $item) {
                foreach ($item as $row) {
                    $update_data[$row['date']] = $update_data[$row['date']] ?? ['step' => 4];

                    $update_data[$row['date']] = array_merge($update_data[$row['date']], $row['data']);
                }
            }

            foreach ($update_data as $date => $item) {
                $tech_id = $id_date_mapping[$date] ?? 0;

                $Tech->update_data($item, $tech_id);
            }
        });

        $final = microtime(true) - $start;

        return true;
    }

    public static function getInstance()
    {
        return new self();
    }
}
