<?php

namespace App\abstracts;

use App\logic\Fund_logic;
use App\logic\Profit_logic;
use App\logic\Record_logic;
use App\logic\Stock_logic;
use App\Traits\Mathlib;
use Illuminate\Support\Facades\Storage;

class JackStrategy
{
    use Mathlib;

    protected $Stock_data = '';

    protected $buy_date = [];

    protected $sell_date = [];

    protected $insert_content = [];

    protected $rule_avg_volume_days = 20;

    protected $volume_data = [];

    protected $volume_limit = 500;

    protected $Stock_avg_price = [];

    protected $stock_info = [];

    protected $stock_data = [];

    protected $profit_data = [];

    protected $year;

    protected $MA;

    protected $simulate_data = [];

    protected $stockInfo = [];

    protected $fund = [];

    // 過濾好的stock_id

    protected $profit_data_filter = [];

    private $file_name;

    private $log_title;

    private $start = '2016-01-01';

    private $end = '2018-12-31';

    private $ori_content = '';

    private $content = '';

    private $last_code = 0;

    private $Stock = '';

    private $not_read = [];

    private $code = 0;

    private $stock_id = 0;

    private $result = [];

    private $new_content = '';

    protected function set_file_name($fileName)
    {
        $this->file_name = $fileName;
    }

    protected function set_log_title($log_title)
    {
        $this->log_title = $log_title;
    }

    // 算平均成交量 [id => [date => volume]]

    protected function set_volume()
    {
        foreach ($this->year as $year) {
            $this->volume_data[$year] = collect($this->stock_data[$year])->mapWithKeys(function ($item, $stock_id) {
                $data = [];
                $total = \count($item);
                for ($i = 0; $i <= $total - $this->rule_avg_volume_days; ++$i) {
                    $subArray = \array_slice($item, $i, $this->rule_avg_volume_days);
                    $avgVolume = collect($subArray)->pluck('volume')->avg();
                    $last = array_pop($subArray);
                    $last_date = $last->data_date;
                    $data[$last_date] = $avgVolume;
                }

                return [$stock_id => $data];
            });
        }
    }

    // 計算收盤價超過指定平均價的天數

    protected function count_over_avg_days(int $year, int $MA_days, string $assignDate, array $closePrice, int $stock_id): array
    {
        $result = [
            'status' => false,
            'days' => 0,
        ];

        $MA_data = $this->MA[$year][$MA_days][$stock_id] ?? [];

        if (isset($MA_data[$assignDate])) {
            $result['status'] = round($MA_data[$assignDate], 2) < round($closePrice[$assignDate], 2); // 確認指定日期，均價真的低於收盤價

            foreach ($MA_data as $dateKey => $avg_price) {
                if (strtotime($dateKey) <= strtotime($assignDate)) {
                    $result['days'] = round($closePrice[$dateKey], 2) >= round($avg_price, 2) ? $result['days'] + 1 : 0;
                }
            }
        }

        return $result;
    }

    // 計算 三大法人 連續買賣超天數

    protected function count_fund_over_days(int $year, int $stock_id, string $assignDate, string $column): int
    {
        $result = 0;

        $fund_data = $this->fund[$year][$stock_id] ?? [];

        if (isset($fund_data[$assignDate])) {
            foreach ($fund_data as $dateKey => $item) {
                if (strtotime($dateKey) <= strtotime($assignDate)) {
                    $result = (int) ($item[$column]) > 0 ? $result + 1 : 0;
                } else {
                    break;
                }
            }
        }

        return $result;
    }

    protected function setTradeDate()
    {
    }

    protected function count()
    {
        //  建立檔案

        $this->create_file();

        //  設定變數

        $this->set();

        //  過程

        $this->setTradeDate();

        $this->format();

        //  記錄結果

        $this->record();

        return true;
    }

    private function create_file()
    {
        if (file_exists(storage_path('app/'.$this->file_name)) === false) {
            Storage::put($this->file_name, '');
        }
    }

    private function set()
    {
        Record_logic::getInstance()->write_operate_log($action = $this->log_title, $content = 'in process');

        $content = Storage::get($this->file_name);

        $this->ori_content = $content;

        $content = explode("\n", $content);

        $content = array_filter($content, 'trim');

        $last = explode(',', end($content));

        $this->content = $content;

        $this->last_code = $last[0];

        $this->Stock = Stock_logic::getInstance();

        //  股價為 -- 的項目計算上有誤差，撈出來排除掉

        $this->set_filter_code();

        // 股票清單

        $this->stock_info = $this->Stock->get_all_stock_info();

        // 用營收篩選出目標股票

        $this->set_profit();

        // 把該股近2016-2018年股票資料帶出來

        foreach ($this->year as $year) {
            $this->stock_data[$year] = $this->Stock->get_stock_data_assign_year($year, $this->profit_data_filter[$year]);
            $this->fund[$year] = Fund_logic::getInstance()->get($year, $this->profit_data_filter[$year]);
        }

        // 帶出成交量

        $this->set_volume();

        // 算平均線

        $this->setMA(10);
        $this->setMA(20);
        $this->setMA(60);

        // 算模擬資料

        $this->simulate_data();

        $this->stockInfo = $this->Stock->get_stock_info_by_stock_id();
    }

    private function set_filter_code()
    {
        $this->not_read = $this->Stock->get_stock_by_none_price()->pluck('code')->toArray();
    }

    // 用毛利/EPS篩選

    private function set_profit()
    {
        $find_year = [];
        $last_year = $this->year[\count($this->year) - 1];

        for ($i = $this->year[0] - 3; $i <= $last_year; ++$i) {
            $find_year[] = $i;
        }

        $profit_data = collect($find_year)->mapWithKeys(function ($yearItem) {
            return [
                $yearItem => Profit_logic::getInstance()->get_list($yearItem)->toArray(),
            ];
        });

        foreach ($profit_data as $year => $item) {
            foreach ($item as $stock_id => $row) {
                $this->profit_data[$stock_id] = $this->profit_data[$stock_id] ?? [];
                $this->profit_data[$stock_id]['profit'][$year] = $row['gross_profit_percent'];
                $this->profit_data[$stock_id]['eps'][$year] = $row['eps'];
            }
        }

        /*
            近3年毛利率平均 > 20%
            每股盈餘(EPS)連續3年遞增
        */

        foreach ($this->year as $year) {
            $this->profit_data_filter[$year] = collect($this->profit_data)->filter(function ($item) use ($year) {
                $eps = collect($item['eps'])->filter(function ($item, $key) use ($year) {
                    return \in_array($key, [$year - 3, $year - 2, $year - 1], true);
                });
                $eps_ori = $eps->sortKeys()->toArray();
                $eps_sort = $eps->sort()->toArray();
                $profit = collect($item['profit'])->filter(function ($item, $key) use ($year) {
                    return \in_array($key, [$year - 3, $year - 2, $year - 1], true);
                });

                return $profit->avg() > 20 && $eps_sort === $eps_ori;
            })->keys()->toArray();
        }
    }

    // 算平均線 [id => [date => avg]]

    private function setMA(int $days)
    {
        foreach ($this->year as $year) {
            $this->MA[$year][$days] = $this->MA[$year][$days] ?? [];

            $this->MA[$year][$days] = collect($this->stock_data[$year])->mapWithKeys(function ($item, $stock_id) use ($days) {
                $data = [];
                $total = \count($item);
                for ($i = 0; $i <= $total - $days; ++$i) {
                    $subArray = \array_slice($item, $i, $days);
                    $avgVolume = collect($subArray)->pluck('close')->avg();
                    $last = array_pop($subArray);
                    $last_date = $last->data_date;
                    $data[$last_date] = $avgVolume;
                }

                return [$stock_id => $data];
            })->toArray();
        }
    }

    // 算模擬交易股價 [id => [date => price]]

    private function simulate_data()
    {
        foreach ($this->year as $year) {
            $this->simulate_data[$year] = collect($this->stock_data[$year])->mapWithKeys(function ($item, $stock_id) {
                $data = collect($item, $stock_id)->mapWithKeys(function ($item) {
                    $avg = $this->except($item->open + $item->highest + $item->lowest + $item->close, 4);

                    return [$item->data_date => [
                        'data_date' => $item->data_date,
                        'open' => $item->open,
                        'close' => $item->close,
                        'highest' => $item->highest,
                        'lowest' => $item->lowest,
                        'simulate_price' => round($avg, 2),
                    ]];
                })->toArray();

                return [$stock_id => $data];
            });
        }
    }

    private function count_avg()
    {
        $sum = array_sum($this->Stock_avg_price);

        $cnt = \count($this->Stock_avg_price);

        return round($sum / $cnt, 2);
    }

    private function format()
    {
        $this->insert_content = collect($this->sell_date)->map(function ($item, $stock_id) {
            return collect($item)->map(function ($item, $key) use ($stock_id) {
                $buy_date = $this->buy_date[$stock_id][$key]['data_date'];

                $buy_price = $this->buy_date[$stock_id][$key]['simulate_price'];

                $sell_date = $item['data_date'];

                $buy_fee = ceil($buy_price * 1000 * 0.001425);
                $sell_fee = ceil($item['simulate_price'] * 1000 * 0.001425);
                $tax = ceil($item['simulate_price'] * 1000 * 0.003);
                $diff = round($item['simulate_price'] - $buy_price, 2);

                return implode(',', [
                    'code' => $this->stockInfo[$stock_id]['code'],
                    'name' => $this->stockInfo[$stock_id]['name'],
                    'buy_date' => $buy_date,
                    'buy_price' => $buy_price,
                    'buy_fee' => $buy_fee,
                    'sell_date' => $sell_date,
                    'sell_price' => $item['simulate_price'],
                    'sell_fee' => $sell_fee,
                    'tax' => $tax,
                    'diff' => $diff,
                    'profit' => ceil(($diff * 1000) - $buy_fee - $sell_fee - $tax),
                    'over_MA60_days' => $this->buy_date[$stock_id][$key]['over_MA60_days'],
                    'over_MA20_days' => $this->buy_date[$stock_id][$key]['over_MA20_days'],
                    'closePrice' => $item['closePrice'],
                    'MA10Price' => $item['MA10Price'],
                ]);
            })->implode("\n");
        })->implode("\n");
    }

    private function record()
    {
        Storage::put($this->file_name, $this->insert_content);
    }
}
