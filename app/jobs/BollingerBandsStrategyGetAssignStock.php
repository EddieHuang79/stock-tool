<?php

namespace App\jobs;

use App\logic\Notice_logic;
use App\logic\SellBuyPercent_logic;
use App\logic\Stock_logic;
use App\logic\TechnicalAnalysis_logic;
use App\Traits\formatLib;

class BollingerBandsStrategyGetAssignStock
{
    use formatLib;

    private $code = [];

    private $stock_id = [];

    private $date;

    private $sellBuyPercent;

    private $sellBuyPercentUpperBound = 0.7;

    private $percentB;

    private $percentBLowerBound = 0.8;

    private $notice_msg = [];

    private $page = 0;

    private $cnt = 0;

    public function __construct()
    {
        $this->code = [3044, 2330];

        $this->get_stock_id();
    }

    public function count($date = '')
    {
        $this->date = $date;

        // 取得當日percentB

        $this->percentB();

        // 取得當日買賣壓力

        $this->sellBuyPercent();

        // 格式化

        $this->format();

        // 傳遞資料

        $notice = new Notice_logic();

        foreach ($this->notice_msg as $msg) {
            $notice->noticeUser($notice_type = 2, $msg);
        }
    }

    public static function getInstance()
    {
        return new self();
    }

    private function get_stock_id()
    {
        $this->stock_id = Stock_logic::getInstance()->get_stock_id($this->code)->pluck('id')->toArray();
    }

    private function sellBuyPercent()
    {
        $this->sellBuyPercent = SellBuyPercent_logic::getInstance()->get_today_result($this->stock_id, $this->date)->mapWithKeys(function ($item) {
            return [$item->code => $item->result];
        })->toArray();
    }

    private function percentB()
    {
        $this->percentB = TechnicalAnalysis_logic::getInstance()->get_today_percentB($this->stock_id, $this->date)->mapWithKeys(function ($item) {
            return [$item->code => $item->percentB];
        })->toArray();
    }

    private function format()
    {
        $this->notice_msg[0] = '';
        $this->notice_msg[0] .= $this->notice_format($msg = '--策略條件--');
        $this->notice_msg[0] .= $this->notice_format($msg = '--指定股票資料回報--');
        $this->notice_msg[0] .= $this->notice_format($msg = 'Percent B < '.$this->percentBLowerBound);
        $this->notice_msg[0] .= $this->notice_format($msg = '買賣壓力 > '.$this->sellBuyPercentUpperBound);
        $this->notice_msg[0] .= $this->notice_format($msg = '滿足以上條件則該出貨！');

        $code = collect($this->code);

        $code->map(function ($code) {
            $percentB = isset($this->percentB[$code]) ? $this->percentB[$code] : 0;
            $sellBuyPercent = isset($this->sellBuyPercent[$code]) ? $this->sellBuyPercent[$code] : 0;
            $result = $percentB < $this->percentBLowerBound && $sellBuyPercent > $this->sellBuyPercentUpperBound ? '已達出貨標準！' : '持續觀望！';
            $this->notice_msg[$this->page] = isset($this->notice_msg[$this->page]) ? $this->notice_msg[$this->page] : '';
            $this->notice_msg[$this->page] .= $this->notice_format($msg = '-----');
            $this->notice_msg[$this->page] .= $this->notice_format($msg = '股票代號:'.$code);
            $this->notice_msg[$this->page] .= $this->notice_format($msg = 'BB%:'.$percentB);
            $this->notice_msg[$this->page] .= $this->notice_format($msg = '買賣壓力:'.$sellBuyPercent);
            $this->notice_msg[$this->page] .= $this->notice_format($msg = '分析結果:'.$result);
            $this->notice_msg[$this->page] .= $this->notice_format($msg = '-----');
            $this->cnt = $this->cnt + 1;
            $this->page += $this->cnt % 10 === 0 ? 1 : 0;
        });

        if ($code->isNotEmpty() === false) {
            $this->notice_msg[0] .= $this->notice_format($msg = '無指定資料');
        }
    }
}
