<?php

namespace App\jobs;

use App\logic\Holiday_logic;
use App\logic\Record_logic;
use App\logic\Redis_tool;
use App\logic\Stock_logic;
use App\Traits\SchemaFunc;
use App\Traits\stockFileLib;
use Ixudra\Curl\Facades\Curl;

class AccessCSV
{
    use SchemaFunc;
    use stockFileLib;

    // 		取得股票基本五項資料轉存到文字檔
    /*

            開盤: open
            收盤: close
            最高: highest
            最低: lowest
            成交量: trading_volume

    */

    public function get_stock_file($code, $start, $end)
    {
        try {
            $end = mktime(0, 0, 0, date('m', strtotime($end)), 1, date('Y', strtotime($end)));

            $start = mktime(0, 0, 0, date('m', strtotime($start)), 1, date('Y', strtotime($start)));

            $now = $start;

            $i = 0;

            while ($now <= $end) {
                $date = date('Ymd', $now);

                $stock_data = Stock_logic::getInstance()->get_stock($code);

                $url = $stock_data->type === 1 ? $this->get_TWSE_listed_url($date, $code) : $this->get_TPEx_listed_url($date, $code);

                $data = Curl::to($url)->get();

                $this->saveStockFile($data, $date, $code, $stock_data->type);

                $i = $i + 1;

                $now = mktime(0, 0, 0, (int) date('m', $start) + $i, 1, date('Y', $start));
            }
        } catch (\Exception $e) {
            $this->set_error_msg($e, $position = 'get_stock_file');
        }

        return true;
    }

    //      取得股票三大法人買賣超資料轉存到文字檔 上市

    public function get_stock_fund_file()
    {
        try {
            $end = time();

            $start = Redis_tool::getInstance()->getFundExcuteKey();

            $now = $start;

            while ($now <= $end) {
                $date = date('Ymd', $now);

                $isHoliday = Holiday_logic::getInstance()->is_holiday($now);

                if ($isHoliday === false) {
                    $url = $this->get_TWSE_fund_url($date);

                    $data = Curl::to($url)->get();

                    $this->saveStockFundFile($type = 1, $data, $date);

                    $now += 86400;

                    Redis_tool::getInstance()->setFundExcuteKey($now);

                    Record_logic::getInstance()->write_operate_log($action = 'auto_get_fund_data type1', $content = $date);

                    break;
                }

                $now += 86400;
            }
        } catch (\Exception $e) {
            $this->set_error_msg($e, $position = 'get_stock_fund_file');
        }

        return true;
    }

    //      取得股票三大法人買賣超資料轉存到文字檔 上櫃

    public function get_stock_fund_file2()
    {
        try {
            $end = time();

            $start = Redis_tool::getInstance()->getFundExcuteKey2();

            $now = $start;

            while ($now <= $end) {
                $date = date('Ymd', $now);

                $isHoliday = Holiday_logic::getInstance()->is_holiday($now);

                if ($isHoliday === false) {
                    $url = $this->get_TPEx_fund_url($date);

                    $data = Curl::to($url)->get();

                    $data = json_decode($data, true);

                    $this->saveStockFundFile2($type = 2, $data['aaData'], $date);

                    $now += 86400;

                    Redis_tool::getInstance()->setFundExcuteKey2($now);

                    Record_logic::getInstance()->write_operate_log($action = 'auto_get_fund_data type2', $content = $date);

                    break;
                }

                $now += 86400;
            }
        } catch (\Exception $e) {
            $this->set_error_msg($e, $position = 'get_stock_fund_file');
        }

        return true;
    }

    /*

        條件
        5個工作天內(簡單做，日期-7)
        Macd 最高（最低）後，轉折連續兩天或三天低於（高於）前日，隔一天放空（做多
        目標: 台指期

    */

    // 	更新每日各股資訊，取完檔案立刻寫入資料庫，每一次限制100筆，每筆資料取的區間為5秒
    // 	每寫入一次就更新redis值 [code => date]

    public function update_daily_data($type = 1)
    {
        $timeStamp = time();
        // $timeStamp = mktime(0,0,0,12,31,2019);

        //  刪除清單

        $Redis = Redis_tool::getInstance();

        $Redis->delUpdateDaily();

        //  取得股票設定

        $config = Stock_logic::getInstance()->get_all_stock_update_date_new($type);

        sleep($config['sec']);

        //  取得已更新清單

        $update_list = $Redis->getUpdateDaily(date('Ymd', $timeStamp));

        $update_list = collect($update_list)->map(function ($item) {
            return (int) $item;
        })->toArray();

        // 待更新的股票資料

        $wait_to_update_stock = Stock_logic::getInstance()->get_wait_to_update_stock($config['start'], $config['end'], $update_list);

        // 取得股票類型

        $code_type_mapping = Stock_logic::getInstance()->get_stock_type();

        foreach ($wait_to_update_stock as $code) {
            $date = date('Ym01', $timeStamp);

            $type = isset($code_type_mapping[$code]) ? $code_type_mapping[$code] : 1;

            $url = $type === 1 ? $this->get_TWSE_listed_url($date, $code) : $this->get_TPEx_listed_url($date, $code);

            $data = Curl::to($url)->get();

            $this->saveStockFile($data, $date, $code, $type);

            Record_logic::getInstance()->write_operate_log($action = 'update_daily_data', $content = $code);

            $Redis->setUpdateDaily(date('Ymd', $timeStamp), (int) $code);
            $Redis->setUpdateFailProcessDaily(date('Ymd', $timeStamp), (int) $code);
        }

        return true;
    }

    public function update_fail_daily_data($date)
    {
        $date = date('Ymd', strtotime($date));

        //  刪除清單

        $Redis = Redis_tool::getInstance();

        // 待更新的股票資料

        $fail = $Redis->getUpdateFailProcessDaily($date);

        $limit = \count($fail) > 10 ? 10 : \count($fail);

        $wait_to_update_stock = \array_slice($fail, 0, $limit);

        $pending = \array_slice($fail, $limit, \count($fail) - $limit);

        //  更新陣列

        $Redis->delUpdateFailProcessDaily();

        foreach ($pending as $code) {
            $Redis->setUpdateFailProcessDaily($date, (int) $code);
        }

        // 取得股票類型

        $code_type_mapping = Stock_logic::getInstance()->get_stock_type();

        foreach ($wait_to_update_stock as $code) {
            $date = date('Ym01', strtotime($date));

            $type = isset($code_type_mapping[$code]) ? $code_type_mapping[$code] : 1;

            $url = $type === 1 ? $this->get_TWSE_listed_url($date, $code) : $this->get_TPEx_listed_url($date, $code);

            $data = Curl::to($url)->get();

            $this->saveStockFile($data, $date, $code, $type);

            Record_logic::getInstance()->write_operate_log($action = 'update_fail_daily_data', $content = $code);

            $Redis->setUpdateDaily(date('Ymd'), (int) $code);

            sleep(5);
        }

        return true;
    }

    // 		Cron Job 自動取得所有股票資料
    /*

            區間: 近3年
            每次: 1份檔案(避免鎖IP)
            type: 撈資料的區間

    */

    public function auto_get_data($type = 0)
    {
        $config = $this->get_delay_config($type);

        $limit = 1;

        $start = mktime(0, 0, 0, 1, 1, date('Y') - 3);

        // 存在的檔案

        $exist_file = collect($config['file'])->filter(function ($value) {
            return strpos($value, date('Ym')) !== false;
        })->map(function ($value) {
            $tmp = explode('/', $value);

            return (int) ($tmp[2]);
        })->values()->toArray();

        // 股票資料

        $list = Stock_logic::getInstance()->get_stock_option();

        $list = collect($list['data'])->pluck('value')->filter(function ($value) use ($exist_file, $config) {
            return !\in_array((int) $value, $exist_file, true) && $value >= $config['code_start'] && $value <= $config['code_end'];
        })->sort()->values()->toArray();

        Record_logic::getInstance()->write_operate_log($action = 'auto_get_data type'.$type, $content = 'in process');

        foreach ($list as $code) {
            $i = 0;

            while ($limit > 0) {
                // 已存在的檔案

                $exists_stock_file = $this->get_exist_data($code);

                $loop_date = date('Ymd', mktime(0, 0, 0, (int) date('m', $start) + $i, 1, date('Y', $start)));

                ++$i;

                if (strtotime($loop_date) > time()) {
                    break;
                }

                if (!\in_array($loop_date, $exists_stock_file, true)) {
                    $this->get_stock_file($code, $loop_date, $loop_date);

                    --$limit;
                } else {
                    continue;
                }

                if ($limit <= 0 || \in_array(date('Ymd'), $exists_stock_file, true)) {
                    break 2;
                }
            }
        }

        return true;
    }

    //      Cron Job 自動取得所有股票三大法人買賣超
    /*

            區間: 近3年
            每次: 1份檔案(避免鎖IP)
            type: 撈資料的區間

    */

    public function auto_get_fund_data()
    {
        // 上市

        $this->get_stock_fund_file();

        return true;
    }

    public function auto_get_fund_data2()
    {
        // 上櫃

        $this->get_stock_fund_file2();

        return true;
    }

    public static function getInstance()
    {
        return new self();
    }

    // 		上市網址 抓股價

    private function get_TWSE_listed_url($date, $code)
    {
        return 'https://www.twse.com.tw/exchangeReport/STOCK_DAY?response=csv&date='.$date.'&stockNo='.$code;
    }

    // 		上櫃網址 抓股價
    //		$response = Curl::to( $url )->withResponseHeaders()->returnResponseObject()->get(); 破解

    private function get_TPEx_listed_url($date, $code)
    {
        $date = $this->year_change($date);

        return 'https://www.tpex.org.tw/web/stock/aftertrading/daily_trading_info/st43_download.php?l=zh-tw&d='.$date.'&stkno='.$code.'&s=[0,asc,0]';
    }

    //      上市網址 抓三大法人買賣超

    private function get_TWSE_fund_url($date)
    {
        return 'https://www.twse.com.tw/fund/T86?response=csv&date='.$date.'&selectType=ALLBUT0999';
    }

    //      上櫃網址 抓三大法人買賣超

    private function get_TPEx_fund_url($date)
    {
        $day = substr($date, 6, 2);

        $date = $this->year_change($date);

        $date = $date.'/'.$day;

        return 'https://www.tpex.org.tw/web/stock/3insti/daily_trade/3itrade_hedge_result.php?l=zh-tw&se=EW&t=D&d='.$date.'&_='.(int) (microtime(true) * 1000);
    }

    // 	多筆cron的分批執行

    private function get_delay_config($type)
    {
        $result = [
            'sleep_second' => 0,
            'code_start' => 0,
            'code_end' => 0,
            'file' => [],
        ];

        switch ($type) {
            case 1:

                $result = [
                    'sleep_second' => 0,
                    'code_start' => 3500,
                    'code_end' => 3799,
                    'file' => $this->get_dir_files('st3000'),
                ];

                break;
            case 2:

                $result = [
                    'sleep_second' => 5,
                    'code_start' => 3500,
                    'code_end' => 3799,
                    'file' => $this->get_dir_files('st3000'),
                ];

                break;
            case 3:

                $result = [
                    'sleep_second' => 10,
                    'code_start' => 3500,
                    'code_end' => 3799,
                    'file' => $this->get_dir_files('st3000'),
                ];

                break;
            case 4:

                $result = [
                    'sleep_second' => 15,
                    'code_start' => 3500,
                    'code_end' => 3799,
                    'file' => $this->get_dir_files('st3000'),
                ];

                break;
            case 5:

                $result = [
                    'sleep_second' => 20,
                    'code_start' => 3500,
                    'code_end' => 3799,
                    'file' => $this->get_dir_files('st3000'),
                ];

                break;
            case 6:

                $result = [
                    'sleep_second' => 25,
                    'code_start' => 3500,
                    'code_end' => 3799,
                    'file' => $this->get_dir_files('st3000'),
                ];

                break;
            case 7:

                $result = [
                    'sleep_second' => 30,
                    'code_start' => 3500,
                    'code_end' => 3799,
                    'file' => $this->get_dir_files('st3000'),
                ];

                break;
        }

        return $result;
    }
}
