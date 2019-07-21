<?php

namespace App\jobs;

use App\logic\Stock_logic;
use App\logic\TechnicalAnalysis_logic;
use App\logic\Holiday_logic;
use App\logic\SellBuyPercent_logic;
use App\logic\Notice_logic;
use App\Traits\Mathlib;
use App\Traits\formatLib;
use App\logic\Record_logic;

class BollingerBandsStrategyBuyingJobs
{

    use Mathlib, formatLib;

    private $Tech = '';

    private $Stock = '';

    private $start = '';

    private $end = '';

    private $data = '';

    private $not_read = [];

    private $sellBuyPercent = '';

    private $code = '';

    private $notice_data = '';

    private $notice_msg = [];

    private $rule_percentB = 0.8;

    private $rule_sellBuyPercent = 0.8;

    private $continue_days = [];

    private $notCntList = [1108,1109,1217,1218,1220,1304,1308,1309,1313,1314,1337,1341,1409,1414,1417,1423,1437,1440,1442,1444,1445,1447,1455,1459,1460,1467,1468,1471,1474,1512,1514,1517,1524,1528,1540,1587,1604,1605,1608,1609,1611,1612,1616,1618,1626,1701,1707,1709,1711,1712,1714,1717,1718,1720,1721,1722,1724,1725,1727,1731,1732,1733,1734,1802,1806,1809,1810,1902,1904,1905,1907,2007,2009,2010,2014,2017,2020,2022,2023,2024,2030,2032,2038,2107,2109,2208,2302,2305,2312,2316,2323,2329,2331,2332,2338,2340,2342,2344,2349,2352,2353,2358,2359,2363,2365,2367,2368,2369,2371,2374,2380,2387,2388,2390,2399,2401,2402,2405,2406,2409,2413,2414,2417,2426,2427,2429,2431,2434,2438,2440,2442,2443,2444,2453,2457,2460,2461,2465,2468,2471,2475,2482,2483,2484,2486,2489,2491,2495,2499,2501,2504,2505,2506,2511,2514,2515,2516,2520,2528,2530,2535,2537,2538,2543,2546,2547,2601,2603,2605,2607,2609,2610,2611,2613,2614,2615,2617,2618,2701,2702,2705,2706,2712,2801,2812,2816,2820,2832,2834,2836,2838,2841,2845,2849,2851,2852,2855,2867,2880,2883,2884,2885,2887,2888,2889,2890,2891,2892,2897,2903,2906,2911,2913,2923,3002,3011,3013,3021,3024,3025,3027,3028,3029,3031,3033,3037,3038,3041,3043,3046,3047,3048,3049,3050,3051,3052,3056,3057,3058,3062,3149,3229,3266,3296,3308,3311,3312,3321,3383,3419,3432,3481,3494,3519,3535,3536,3550,3557,3576,3579,3591,3593,3622,3669,3682,3686,3694,3698,3701,3703,3704,4108,4119,4148,4306,4414,4564,4720,4766,4930,4934,4956,4960,4961,4967,5203,5215,5225,5234,5258,5259,5469,5484,5515,5519,5521,5525,5531,5533,5607,5608,5880,5906,6005,6024,6115,6116,6117,6120,6131,6133,6141,6142,6152,6164,6168,6172,6191,6215,6225,6226,6235,6243,6251,6288,6289,6405,6431,6443,6558,6655,6668,6670,6671,6674,8011,8021,8028,8033,8039,8046,8070,8101,8104,8105,8110,8201,8215,8443,8940,9103,910482];

    private $volume_data = [];

    private $rule_avg_volume = 500;

    private $rule_avg_volume_days = 10;

    private $page = 0;

    private $cnt = 0;

    private $now_date;

    /*

        PercentB >= 0.8
        SellBuyPercent

        測試輔助條件，SellBuyPercent

    */

    private function set()
    {

        $Holiday = Holiday_logic::getInstance();

        $this->Tech = TechnicalAnalysis_logic::getInstance();

        $this->Stock = Stock_logic::getInstance();

        $this->start = $this->now_date;

        $this->end = $Holiday->get_work_date( $before_days = 1, $this->now_date, $type = 2 );

        $this->not_read = $this->Stock->get_stock_by_none_price()->pluck("code")->toArray();

        $this->sellBuyPercent = SellBuyPercent_logic::getInstance()->get_data_by_range( $this->start, $this->end )->filter(function ($item){
            return $item->result > 0 && $item->result < $this->rule_sellBuyPercent && !in_array($item->code, $this->not_read) ;
        })->mapWithKeys(function ($item){
            return [$item->code => $item->result];
        })->toArray();

        $this->data = $this->Tech->get_data_by_range( $this->start, $this->end )->filter(function ($item){
            return !in_array($item->code, $this->not_read)
                && !in_array($item->code, $this->notCntList)
                && $item->percentB >= $this->rule_percentB;
        });

        $start = $Holiday->get_work_date( $before_days = 10, $now_date = $this->now_date, $type = 1 );

        $end = $this->now_date;

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

    }

    private function set_volume( $code )
    {

        $start = Holiday_logic::getInstance()->get_work_date( $before_days = $this->rule_avg_volume_days, $now_date = $this->now_date, $type = 1 );

        $end = $this->now_date;

        $volume_data = $this->Stock->get_stock_data_by_date_range( $start, $end, $code );

        $this->volume_data[$code] = collect( $volume_data[$code] )->pluck("volume")->avg();

    }

    private function getStockData()
    {

        $Stock_data = $this->Stock->get_stock_data_by_date_range( $this->start, $this->end, $this->code );

        $result = collect( $Stock_data[$this->code] )->mapWithKeys(function ($item) {
            return [
                $item->lowest
            ];
        })->shift();

        return $result;

    }

    private function process()
    {

        $this->notice_data = $this->data->map(function ($item) {
            $this->code = $item->code;
            $item->sellBuyPercent = isset($this->sellBuyPercent[$item->code]) ? $this->sellBuyPercent[$item->code] : 0 ;
            $item->continue_days = isset($this->continue_days[$item->code]) ? $this->continue_days[$item->code] : 0 ;
            $item->price = $this->getStockData() ;
            return $item;
        })->filter(function ($item){

            $this->set_volume( $item->code );

            return !empty($item->sellBuyPercent)
                && $this->volume_data[$item->code] > $this->rule_avg_volume
                && $item->continue_days < 3;

        })->sortBy("sellBuyPercent");

    }

    // 格式化

    private function format()
    {

        $this->notice_msg[0] = '';
        $this->notice_msg[0] .= $this->notice_format( $msg = '--策略條件--' ) ;
        $this->notice_msg[0] .= $this->notice_format( $msg = '--進場訊號--' ) ;
        $this->notice_msg[0] .= $this->notice_format( $msg = 'Percent B > ' . $this->rule_percentB ) ;
        $this->notice_msg[0] .= $this->notice_format( $msg = '買賣壓力 < ' . $this->rule_sellBuyPercent ) ;
        $this->notice_msg[0] .= $this->notice_format( $msg = $this->rule_avg_volume_days . '日平均成交量 > ' . $this->rule_avg_volume ) ;
        $this->notice_msg[0] .= $this->notice_format( $msg = '持續天數 < 3 天') ;

        $this->notice_data->map(function ($item){
            $this->notice_msg[$this->page] = isset($this->notice_msg[$this->page]) ? $this->notice_msg[$this->page] : '';
            $this->notice_msg[$this->page] .= $this->notice_format( $msg = '-----' );
            $this->notice_msg[$this->page] .= $this->notice_format( $msg = '股票代號:' . $item->code );
            $this->notice_msg[$this->page] .= $this->notice_format( $msg = '買賣壓力:' . $item->sellBuyPercent );
            $this->notice_msg[$this->page] .= $this->notice_format( $msg = 'BB%:' . $item->percentB );
            $this->notice_msg[$this->page] .= $this->notice_format( $msg = 'BB%  > ' . $this->rule_percentB . '的持續天數: ' . $item->continue_days ) ;
            $this->notice_msg[$this->page] .= $this->notice_format( $msg = '近' . $this->rule_avg_volume_days . '日平均成交量: ' . $this->volume_data[$item->code] ) ;
            $this->notice_msg[$this->page] .= $this->notice_format( $msg = '-----' );
            $this->cnt = $this->cnt + 1;
            $this->page += $this->cnt % 10 === 0 ? 1 : 0 ;
        });

        if ( $this->notice_data->isNotEmpty() === false )
        {

            $this->notice_msg[0] .= $this->notice_format( $msg = '無符合資料' ) ;

        }

    }

    public function count( $date = '' )
    {

        $this->now_date = $date;

        Record_logic::getInstance()->write_operate_log( $action = 'BollingerBandsStrategyBuyingJobs', $content = "process" );

        //  設定變數

        $this->set();

        $this->process();

        $this->format();

        // 通知

        $notice = new Notice_logic();

        foreach ($this->notice_msg as $msg)
        {

            $notice->noticeUser( $notice_type = 2, $msg );

        }

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}
