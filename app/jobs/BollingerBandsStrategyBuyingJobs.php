<?php

namespace App\jobs;

use App\logic\Stock_logic;
use App\logic\TechnicalAnalysis_logic;
use App\logic\Holiday_logic;
use App\logic\SellBuyPercent_logic;
use App\logic\Notice_logic;
use App\Traits\Mathlib;
use App\Traits\formatLib;

class BollingerBandsStrategyBuyingJobs
{

    use Mathlib, formatLib;

    private $Tech = '';

    private $Stock = '';

    private $start = '';

    private $end = '';

    private $data = '';

    private $not_read = '';

    private $sellBuyPercent = '';

    private $Stock_data = '';

    private $code = '';

    private $avg_volume = '';

    private $notice_data = '';

    private $notice_msg = '';

    private $rule_percentB = 0.8;

    private $rule_sellBuyPercent = 0.9;

    private $rule_avg_volume = 500;

    private $rule_bandwidth = 0.05;

    private $rule_avg_volume_days = 10;

    private $continue_days = [];

    private $volume_data = [];


    /*

        PercentB >= 0.8 就回報

        測試輔助條件，SellBuyPercent

    */

    private function set()
    {

        $Holiday = Holiday_logic::getInstance();

        $this->Tech = TechnicalAnalysis_logic::getInstance();

        $this->Stock = Stock_logic::getInstance();

        $this->start = $Holiday->get_work_date( $before_days = 1, $now_date = date("Y-m-d"), $type = 1 );

        $this->end = $Holiday->get_work_date( $before_days = 1, $now_date = date("Y-m-d"), $type = 2 );

        $this->not_read = $this->Stock->get_stock_by_none_price()->pluck("code")->toArray();

        $this->sellBuyPercent = SellBuyPercent_logic::getInstance()->get_data_by_range( $this->start, $this->end )->filter(function ($item){
            return $item->result > 0 && $item->result < $this->rule_sellBuyPercent && !in_array($item->code, $this->not_read) ;
        })->mapWithKeys(function ($item){
            return [$item->code => $item->result];
        })->toArray();

        $this->data = $this->Tech->get_data_by_range( $this->start, $this->end )->filter(function ($item){
            return !in_array($item->code, $this->not_read) && $item->percentB >= $this->rule_percentB;
        });

        $start = $Holiday->get_work_date( $before_days = $this->rule_avg_volume_days, $now_date = date("Y-m-d"), $type = 1 );

        $end = date("Y-m-d");

        $this->continue_days = $this->Tech->get_data_by_range( $start, $end )->mapToGroups(function ($item){
            return [
                $item->code => [
                    $item->data_date => $item->percentB
                ]
            ];
        })->map(function ($item){
            $item = $item->mapWithKeys(function ($item){
                $key = array_keys($item)[0];
                $value = $item[$key];
                return [ $key => $value ];
            })->sortKeysDesc()->toArray();
            return $item;
        })->map(function ($item){
            $cnt = 0;
            foreach ($item as $row) {
                $cnt += $row > $this->rule_percentB ? 1 : 0 ;
                if ( $row <= $this->rule_percentB ) {
                    break;
                }
            }
            return $cnt;
        });

        $this->set_volume();

    }

    private function setStockData()
    {

        $this->Stock_data = $this->Stock->get_stock_data_by_date_range( '2016-01-01', date("Y-m-d"), $this->code );

        $this->Stock_data = collect( $this->Stock_data[$this->code] )->mapWithKeys(function ($item) {
            return [
                $item->data_date => $this->except( $item->highest + $item->lowest, 2 )
            ];
        })->toArray();

    }

    private function count_avg()
    {

        $sum = array_sum( $this->Stock_data );

        $cnt = count( $this->Stock_data );

        return round( $sum/$cnt, 2 );

    }

    private function set_volume()
    {

        $start = Holiday_logic::getInstance()->get_work_date( $before_days = $this->rule_avg_volume_days, $now_date = date("Y-m-d"), $type = 1 );

        $end = date("Y-m-d");

        $this->volume_data = $this->Stock->get_stock_data_by_date_range( $start, $end );

        $this->volume_data = collect( $this->volume_data )->map(function ($item) {
            return collect($item)->pluck("volume")->avg();
        });

    }

    private function process()
    {

        $this->notice_data = $this->data->map(function ($item) {
            $this->code = $item->code;
            $item->sellBuyPercent = isset($this->sellBuyPercent[$item->code]) ? $this->sellBuyPercent[$item->code] : 0 ;
            $item->avg_volume = isset($this->volume_data[$item->code]) ? $this->volume_data[$item->code] : 0;
            $item->continue_days = isset($this->continue_days[$item->code]) ? $this->continue_days[$item->code] : 0 ;
            return $item;
        })->filter(function ($item){

            $this->code = $item->code;

            //  股價

            $this->setStockData();

            //  計算平均

            $avg = $this->count_avg();

            return $avg >= 30 && !empty($item->sellBuyPercent) && $item->bandwidth <= $this->rule_bandwidth && $item->avg_volume > $this->rule_avg_volume;

        })->sortBy("sellBuyPercent");

    }

    // 格式化

    private function format()
    {

        $this->notice_msg .= $this->notice_format( $msg = '--策略條件--' ) ;
        $this->notice_msg .= $this->notice_format( $msg = '--進場訊號--' ) ;
        $this->notice_msg .= $this->notice_format( $msg = 'BB% > ' . $this->rule_percentB ) ;
        $this->notice_msg .= $this->notice_format( $msg = '買賣壓力 < ' . $this->rule_sellBuyPercent ) ;
        $this->notice_msg .= $this->notice_format( $msg = '近' . $this->rule_avg_volume_days . '天平均成交量 > ' . $this->rule_avg_volume ) ;
        $this->notice_msg .= $this->notice_format( $msg = 'bandwidth <= ' . $this->rule_bandwidth ) ;

        $this->notice_data->map(function ($item){
            $this->notice_msg .= $this->notice_format( $msg = '-----' );
            $this->notice_msg .= $this->notice_format( $msg = '股票代號:' . $item->code );
            $this->notice_msg .= $this->notice_format( $msg = '買賣壓力:' . $item->sellBuyPercent );
            $this->notice_msg .= $this->notice_format( $msg = '近10天平均成交量:' . $item->avg_volume );
            $this->notice_msg .= $this->notice_format( $msg = 'BB%:' . $item->percentB );
            $this->notice_msg .= $this->notice_format( $msg = 'bandwidth:' . $item->bandwidth );
            $this->notice_msg .= $this->notice_format( $msg = 'BB%  > ' . $this->rule_bandwidth . '的持續天數: ' . $item->continue_days ) ;
            $this->notice_msg .= $this->notice_format( $msg = '-----' );
        });

    }

    public function count()
    {

        //  設定變數

        $this->set();

        $this->process();

        $this->format();

        // 通知

        $notice = new Notice_logic();

        $notice->noticeUser( $notice_type = 2, $this->notice_msg );

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}
