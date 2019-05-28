<?php

namespace App\jobs;

use App\logic\Holiday_logic;
use App\logic\Stock_logic;
use App\logic\TechnicalAnalysis_logic;
use App\Traits\formatLib;
use App\logic\Notice_logic;

class BollingerBandsStrategy
{

    use formatLib;

    private $days = 5;

    private $stock_data = [];

    private $tech_data = [];

    private $notice_data = [];

    private $header = [];

    private $content = [];

    public function __construct()
    {

        $this->header = $this->notice_format( $msg = '--策略條件--' ) ;
        $this->header.= $this->notice_format( $msg = '當最低價格與布林下線交錯' ) ;
        $this->header.= $this->notice_format( $msg = 'Percent B <= 0.2' ) ;
        $this->header.= $this->notice_format( $msg = 'Bandwidth <= 0.1' ) ;

    }


    private function process()
    {

        $this->notice_data = $this->tech_data->filter(function ($item, $code) {
            $lowest = collect( $this->stock_data[$code] )->pluck('lowest')->toArray();
            $result = $item->pluck('lowerBand')->filter(function ($lowerBand, $key) use($lowest) {
                return $lowest[$key] <= $lowerBand;
            });
            return $result->count() > 0;
        })->filter(function ($item) {
            $result = $item->pluck('percentB')->filter(function ($percentB) {
                return $percentB <= 0.2;
            });
            return $result->count() > 0;
        })->filter(function ($item) {
            $result = $item->pluck('bandwidth')->filter(function ($bandwidth) {
                return $bandwidth <= 0.1;
            });
            return $result->count() > 0;
        })->mapToGroups(function ($item, $code){
            return [$code => $item];
        })->reduce(function ($carry, $item){
            return array_merge( $carry, $item->toArray() );
        }, array());

    }

    private function toString()
    {



        $this->content = collect($this->notice_data)->map(function ($item){
            $stock_data = collect( $this->stock_data[$item[0]->code] )->mapToGroups(function ($item){
                return [$item->data_date => $item];
            })->toArray();

            $result = $this->notice_format( $msg = '[股票]' . $item[0]->code ) ;
            $result.= $this->notice_format( $msg = '--資料--' ) ;

            $item = collect( $item )->map(function ($item) use($stock_data) {
                $result = $this->notice_format( $msg = '---' ) ;
                $result.= $this->notice_format( $msg = '[日期]' . $item->data_date ) ;
                $result.= $this->notice_format( $msg = '[最低]' . $stock_data[$item->data_date][0]->lowest ) ;
                $result.= $this->notice_format( $msg = '[布林下線]' . $item->lowerBand ) ;
                $result.= $this->notice_format( $msg = '[BB%]' . $item->percentB ) ;
                $result.= $this->notice_format( $msg = '[BandWidth]' . $item->bandwidth ) ;
                $result.= $this->notice_format( $msg = '---' ) ;
                return $result;
            })->toArray();
            return $result . implode("\r\n", $item);
        });

    }

    private function send_data()
    {

        // 通知

        $notice = new Notice_logic();

        $this->content->map( function ($item) use ($notice) {
            $notice->noticeUser( $notice_type = 2, $notice = $this->header . $item );
        } );

    }

    /*

        當最低價格與下線交錯
        Percent B介於20 - 30 %
        bandwidth <= 0.06

    */

    public function count()
    {

        //  設定開始結束日期

        $end = date("Y-m-d");
//        $end = '2018-11-28';

        $Holiday = new Holiday_logic();

        $start = $Holiday::getInstance()->get_work_date( $this->days, $now_date = $end, $type = 1 );

        //  取得指定區間的股票資料

        $this->stock_data = Stock_logic::getInstance()->get_stock_data_by_date_range( $start, $end );

        //  取得指定區間的技術資料

        $this->tech_data = TechnicalAnalysis_logic::getInstance()->get_data_by_range( $start, $end )->mapToGroups(function ($item){
            return [$item->code => $item];
        });


        /*
            計算條件:
            當最低價格與下線交錯
            Percent B介於20 - 30 %
            bandwidth <= 0.06
        */

        $this->process();

        //  轉換成文字

        $this->toString();

        //  推送Line

        $this->send_data();

    }

    public static function getInstance()
    {

        return new self;

    }


}
