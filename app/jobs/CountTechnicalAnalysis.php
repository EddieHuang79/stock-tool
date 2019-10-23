<?php

namespace App\jobs;

use App\logic\TechnicalAnalysis_logic;
use App\logic\Record_logic;
use App\logic\Stock_logic;
use App\logic\KD_logic;
use App\logic\RSI_logic;
use App\logic\MACD_logic;
use App\logic\BollingerBands_logic;
use App\logic\Holiday_logic;

class CountTechnicalAnalysis
{

    //      計算全部指標

    public function count_all()
    {

        // 找出要計算的前10支股票代號 50 秒

        $Tech = TechnicalAnalysis_logic::getInstance();

        $start = microtime(true);

        $stock_data = $Tech->get_stock_tech_update_date_v2()->toArray();

        $content = !empty($stock_data) ? 'in process' : 'no data';

        Record_logic::getInstance()->write_operate_log( $action = 'auto_count_technical_analysis_all', $content );

        $end_count_day = date("Y-m-d");
        $start_count_day = Holiday_logic::getInstance()->get_work_date( $before_days = 100, $end_count_day, $type = 1 );

        if ($stock_data) 
        {

            foreach ( $stock_data as $item )
            {

                // 流水號

                $stock_id = $item->stock_id;

                // 日期與id的對應

                $Tech_data = $Tech->get_data( $stock_id, $start_count_day, $end_count_day );

                $id_date_mapping = $Tech_data->mapWithKeys( function( $item ) {
                    return [ $item->data_date => $item->id ];
                } )->toArray();

                $all_data = [
                    "KD" => KD_logic::getInstance()->return_data( $stock_id, $id_date_mapping, $Tech, $Tech_data, $start_count_day, $end_count_day ),
                    "RSI" => RSI_logic::getInstance()->return_data( $stock_id, $id_date_mapping, $Tech, $Tech_data, $start_count_day, $end_count_day ),
                    "MACD" => MACD_logic::getInstance()->return_data( $stock_id, $id_date_mapping, $Tech, $Tech_data, $start_count_day, $end_count_day ),
                    "Bollinger" => BollingerBands_logic::getInstance()->return_data( $stock_id, $id_date_mapping, $Tech, $Tech_data, $start_count_day, $end_count_day ),
                ];

                $update_data = [];

                foreach ($all_data as $item) 
                {

                    foreach ($item as $row) 
                    {

                        $update_data[$row["date"]] = $update_data[$row["date"]] ?? ["step" => 4];

                        $update_data[$row["date"]] = array_merge($update_data[$row["date"]], $row["data"]);

                    }

                }

                foreach ($update_data as $date => $item) 
                {

                    $tech_id = $id_date_mapping[$date] ?? 0;

                    $Tech->update_data( $item, $tech_id );

                }

            }

            $final = microtime(true) - $start;

        }

    }

    public static function getInstance()
    {

        return new self;

    }

}
