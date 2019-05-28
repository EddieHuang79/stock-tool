<?php

namespace App\jobs;

use App\logic\Stock_logic;
use App\logic\TechnicalAnalysis_logic;
use App\logic\Holiday_logic;
use App\logic\SellBuyPercent_logic;
use App\logic\Notice_logic;
use App\Traits\Mathlib;
use App\Traits\formatLib;

class BollingerBandsStrategySellingJobs
{

    use Mathlib, formatLib;

    private $Tech = '';

    private $Stock = '';

    private $start = '';

    private $end = '';

    private $data = [];

    private $day1 = [];

    private $day2 = [];

    private $not_read = [];

    private $sellBuyPercent = '';

    private $code = '';

    private $volume_data = [];

    private $notice_msg = '';

    private $rule_percentB = 0.8;

    private $rule_sellBuyPercent = 0.9;

    private $rule_avg_volume = 500;

    private $rule_avg_volume_days = 10;

    /* 賣出訊號，percentB低過0.8就執行 */

    private function set()
    {

        $Holiday = Holiday_logic::getInstance();

        $this->Tech = TechnicalAnalysis_logic::getInstance();

        $this->Stock = Stock_logic::getInstance();

        $this->start = $Holiday->get_work_date( $before_days = 2, $now_date = date("Y-m-d"), $type = 1 );

        $this->end = $Holiday->get_work_date( $before_days = 1, $now_date = date("Y-m-d"), $type = 2 );

        $this->not_read = $this->Stock->get_stock_by_none_price()->pluck("code")->toArray();

        $this->set_sellBuyPercent();

        $this->set_volume();

    }

    private function set_sellBuyPercent()
    {

        $this->sellBuyPercent = SellBuyPercent_logic::getInstance()->get_data_by_range( $this->start, $this->end )->filter(function ($item){
            return $item->result > 0 && !in_array($item->code, $this->not_read) ;
        })->mapToGroups(function ($item){
            return [$item->code => [$item->data_date => $item->result]];
        })->map(function ($item){
            $item = $item->mapWithKeys(function ($item){
                $key = array_keys($item)[0];
                $value = $item[$key];
                return [ $key => $value ];
            })->sortKeysDesc()->toArray();
            return $item;
        })->toArray();

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

        $this->data = $this->Tech->get_data_by_range( $this->start, $this->end );

        $this->day1 = $this->data->mapToGroups(function ($item){
            return [$item->code => $item];
        })->map(function($item){
            return $item[0];
        })->filter(function ($item){
            return !in_array($item->code, $this->not_read)
                && $item->percentB >= $this->rule_percentB
                && isset( $this->sellBuyPercent[$item->code][$item->data_date] )
                && $this->sellBuyPercent[$item->code][$item->data_date] < $this->rule_sellBuyPercent
                && isset( $this->volume_data[$item->code] )
                && $this->volume_data[$item->code] > $this->rule_avg_volume ;
        });

        $this->day2 = $this->data->mapToGroups(function ($item){
            return [$item->code => $item];
        })->filter(function($item){
            return isset($item[1]);
        })->map(function($item){
            return $item[1];
        })->filter(function ($item){
            $item->code = !empty($item) ? $item->code : 0 ;
            return isset( $this->day1[$item->code] ) && $item->percentB < $this->rule_percentB;
        });

    }

    // 格式化

    private function format()
    {

        $this->notice_msg .= $this->notice_format( $msg = '--策略條件--' ) ;
        $this->notice_msg .= $this->notice_format( $msg = '--出場訊號--' ) ;
        $this->notice_msg .= $this->notice_format( $msg = 'BB% < ' . $this->rule_percentB ) ;

        $this->day2->map(function ($item){
            $this->notice_msg .= $this->notice_format( $msg = '-----' );
            $this->notice_msg .= $this->notice_format( $msg = '股票代號:' . $item->code );
            $this->notice_msg .= $this->notice_format( $msg = 'BB%:' . $item->percentB );
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
