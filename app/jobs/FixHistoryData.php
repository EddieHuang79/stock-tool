<?php

namespace App\jobs;

use App\logic\BollingerBands_logic;
use App\logic\KD_logic;
use App\logic\MACD_logic;
use App\logic\Record_logic;
use App\logic\RSI_logic;
use App\logic\Stock_logic;
use App\logic\TechnicalAnalysis_logic;

class FixHistoryData
{
    //      計算全部指標

    public function count_tech(int $year)
    {
        // 找出要計算的前200支股票代號 50 秒

        $Tech = TechnicalAnalysis_logic::getInstance();

        $start = microtime(true);

        $stock_data = $Tech->get_history_stock_tech_update_date_v2($year);

        $content = $stock_data->count() > 0 ? 'in process' : 'no data';

        Record_logic::getInstance()->write_operate_log($action = 'auto_count_history_technical_analysis_all', $content);

        if ($stock_data->count() < 1) {
            return true;
        }

        // $start_count_day = '2016-01-01';
        // $end_count_day = '2016-12-31';

        // 本次執行的所有股票id

        $stock_id = $stock_data->pluck('stock_id')->toArray();

        // 日期與id的對應

        $Tech_data = $Tech->get_history_data($stock_id, $year);

        $stock_price_data = Stock_logic::getInstance()->get_stock_data_assign_year($year, $stock_id);

        $stock_data->filter(function ($item) use ($Tech_data, $stock_price_data) {
            return isset($Tech_data[$item->stock_id]) && isset($stock_price_data[$item->stock_id]);
        })->map(function ($item) use ($Tech_data, $stock_price_data, $Tech, $year) {
            // 流水號

            $stock_id = $item->stock_id;

            $id_date_mapping = $Tech_data[$stock_id]->mapWithKeys(function ($item) {
                return [$item->data_date => $item->id];
            })->toArray();

            $stockPriceData = collect($stock_price_data[$stock_id]);

            $all_data = [
                'KD' => KD_logic::getInstance()->return_data($Tech_data[$stock_id], $stockPriceData),
                'RSI' => RSI_logic::getInstance()->return_data($Tech_data[$stock_id], $stockPriceData),
                'MACD' => MACD_logic::getInstance()->return_data($Tech_data[$stock_id], $stockPriceData),
                'Bollinger' => BollingerBands_logic::getInstance()->return_data($Tech_data[$stock_id], $stockPriceData),
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

                $Tech->update_history_data($item, $tech_id, $year, $stock_id);
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
