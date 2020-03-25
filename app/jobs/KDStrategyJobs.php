<?php

namespace App\jobs;

use App\logic\Holiday_logic;
use App\logic\Notice_logic;
use App\logic\Strategy_logic;
use App\logic\trendAnalysis_logic;
use App\Traits\formatLib;

class KDStrategyJobs
{
    use formatLib;

    /*

        透過通訊軟體自動回報選股條件
        條件
        5個工作天內KD金叉(簡單做，日期-7)
        K || D < 20
        股價介於 20 - 80塊 之間
        5日總成交量 > 2500
        趨勢走平或是往上
    */

    public function daily_info($type, $notice_type = 2)
    {
        $end = date('Y-m-d');

        $Holiday = new Holiday_logic();

        $start = $Holiday::getInstance()->get_work_date($before_days = 5, $now_date = $end, $type = 1);

        $data = Strategy_logic::getInstance()->get_strategy_data($type, $start, $end);

        /*
              趨勢判斷：走平或是往上再回報(至少其中兩天股價大於前一天)
        */

        $data['daily_data'] = trendAnalysis_logic::getInstance()->count($data['daily_data']);

        /*
            KD 條件
            KD金叉，K||D < 20
        */

        $KD_cross = collect($data['KD_data'])->filter(function ($item) {
            return !empty($item['gold_cross']);
        })->filter(function ($item) {
            $last = end($item['gold_cross']);

            return $last['value1'] < 20 || $last['value2'] < 20;
        })->mapWithKeys(function ($item, $code) {
            return [$code => end($item['gold_cross'])];
        });

        /*
            日常條件
            收盤價介於20-80，總成交量 >= 2500
        */

        $daily_data = collect($data['daily_data'])->filter(function ($item, $code) use ($KD_cross) {
            $total = isset($item[0]) ? (int) $item[0]->volume : 0;
            $total += isset($item[1]) ? (int) $item[1]->volume : 0;
            $total += isset($item[2]) ? (int) $item[2]->volume : 0;
            $total += isset($item[3]) ? (int) $item[3]->volume : 0;
            $total += isset($item[4]) ? (int) $item[4]->volume : 0;

            return $item[0]->close >= 20 && $item[0]->close <= 80 && $total >= 2500 && isset($KD_cross[$code]);
        })->toArray();

        // 策略條件

        $strategy_info = Strategy_logic::getInstance()->get_list()->filter(function ($item) {
            return $item->id === 1;
        })->pluck('description')->pop();

        $content = json_decode($strategy_info, true);

        $notice_header = $this->notice_format($msg = '--策略條件--');

        foreach ($content as $row) {
            $notice_header .= $this->notice_format($msg = $row[0]);
        }

        $notice_content = [];

        $index = 0;

        foreach ($daily_data as $code => $item) {
            $notice_content[$index] = isset($notice_content[$index]) ? $notice_content[$index] : '';

            $notice_content[$index] .= $this->notice_format($msg = '-----');
            $notice_content[$index] .= $this->notice_format($msg = '股票代號: '.$code);

            // 資料

            foreach ($item as $row) {
                $notice_content[$index] .= $this->notice_format($msg = '---');
                $notice_content[$index] .= $this->notice_format($msg = '[日期]'.$row->data_date);
                $notice_content[$index] .= $this->notice_format($msg = '[成交量]'.$row->volume);
                $notice_content[$index] .= $this->notice_format($msg = '[開盤]'.$row->open);
                $notice_content[$index] .= $this->notice_format($msg = '[最高]'.$row->highest);
                $notice_content[$index] .= $this->notice_format($msg = '[最低]'.$row->lowest);
                $notice_content[$index] .= $this->notice_format($msg = '[收盤]'.$row->close);
                $notice_content[$index] .= $this->notice_format($msg = '---');
            }

            // KD

            $notice_content[$index] .= $this->notice_format($msg = '[KD交叉日期]'.$KD_cross[$code]['date']);
            $notice_content[$index] .= $this->notice_format($msg = '[K值]'.$KD_cross[$code]['value1']);
            $notice_content[$index] .= $this->notice_format($msg = '[D值]'.$KD_cross[$code]['value2']);

            $notice_content[$index] .= $this->notice_format($msg = '-----');

            if (mb_strlen($notice_content[$index]) > 1000) {
                ++$index;
            }
        }

        foreach ($notice_content as $row) {
            $notice_msg = $this->notice_format($msg = '--篩選結果--');

            $notice_msg .= !empty($row) ? $row : '['.date('Y-m-d H:i:s').']無相關資料';

            // 通知

            $notice = new Notice_logic();

            $notice->noticeUser($notice_type, $notice = $notice_header.$notice_msg);
        }
    }

    public static function getInstance()
    {
        return new self();
    }
}
