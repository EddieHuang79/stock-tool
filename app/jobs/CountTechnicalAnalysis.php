<?php

namespace App\jobs;

use App\logic\TechnicalAnalysis_logic;
use App\logic\Record_logic;
use App\logic\Stock_logic;
use App\logic\KD_logic;
use App\logic\RSI_logic;
use App\logic\MACD_logic;
use App\logic\BollingerBands_logic;


class CountTechnicalAnalysis
{


    // 		自動計算各項技術指標

    public function auto_count_technical_analysis( $type )
    {

        $result = false;

        if ( !empty($type) && is_int($type) )
        {

            // 找出要計算的前10支股票代號

            $Tech = TechnicalAnalysis_logic::getInstance();

            //  2.67 sec

            $stock_data = $Tech->get_stock_tech_update_date( $type )->pluck( "code" )->toArray();

            $content = !empty($stock_data) ? 'in process' : 'no data';

            Record_logic::getInstance()->write_operate_log( $action = 'auto_count_technical_analysis_' . $type, $content );

            if (empty($stock_data))
                return true;

            foreach ( $stock_data as $code )
            {

                // 流水號

                $stock_id = Stock_logic::getInstance()->get_stock( $code )->id;

                // 日期與id的對應

                $Tech_data = $Tech->get_data( $stock_id );

                $id_date_mapping = $Tech_data->mapWithKeys( function( $item ) {
                    return [ $item->data_date => $item->id ];
                } )->toArray();

                switch ( $type )
                {


                    // K & D

                    case 1:

                        KD_logic::getInstance()->count_data( $stock_id, $id_date_mapping, $Tech, $Tech_data );

                        break;

                    // RSI

                    case 2:

                        RSI_logic::getInstance()->count_data( $stock_id, $id_date_mapping, $Tech, $Tech_data );

                        break;

                    // MACD

                    case 3:

                        MACD_logic::getInstance()->count_data( $stock_id, $id_date_mapping, $Tech, $Tech_data );

                        break;

                    // 布林

                    case 4:

                        BollingerBands_logic::getInstance()->count_data( $stock_id, $id_date_mapping, $Tech, $Tech_data );

                        break;


                }

            }

            $result = true;

        }

        return $result;

    }

    public static function getInstance()
    {

        return new self;

    }

}
