<?php

namespace App\jobs;
use Illuminate\Support\Facades\DB;
use App\logic\Record_logic;
use App\model\TechnicalAnalysis;
use App\logic\TechnicalAnalysis_logic;


class SyncFromStockData
{

    // 		建立初始資料

    public function create_init_data()
    {

        Record_logic::getInstance()->write_operate_log( $action = 'create_init_data', $content = 'in process' );

        DB::raw('START TRANSACTION');

        $data = TechnicalAnalysis::getInstance()->create_init_data()->map(function( $item ){
            return [
                "stock_id" 		=> $item->stock_id,
                "stock_data_id" => $item->stock_data_id,
                "code" 			=> $item->code,
                "data_date" 	=> $item->data_date,
                "RSV" 			=> 0.00,
                "K9" 			=> 0.00,
                "D9" 			=> 0.00,
                "RSI5" 			=> 0.00,
                "RSI10" 		=> 0.00,
                "DIFF" 			=> 0.00,
                "MACD" 			=> 0.00,
                "OSC" 			=> 0.00,
                "created_at"   	=> date("Y-m-d H:i:s"),
                "updated_at"    => date("Y-m-d H:i:s")
            ];
        })->toArray();

        TechnicalAnalysis_logic::getInstance()->add_data( $data );

        DB::raw('COMMIT');

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}
