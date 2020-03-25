<?php

namespace App\abstracts;

use App\logic\Holiday_logic;
use App\logic\Record_logic;
use App\logic\SellBuyPercent_logic;
use App\logic\Stock_logic;
use App\logic\TechnicalAnalysis_logic;
use App\Traits\Mathlib;
use Illuminate\Support\Facades\Storage;

class BollingerBandsStrategy
{
    use Mathlib;

    protected $Tech = '';

    protected $Stock_data = '';

    protected $Tech_data = [];

    protected $buy_date = [];

    protected $sell_date = [];

    protected $insert_content = [];

    protected $sellBuyPercent = [];

    protected $rule_avg_volume_days = 10;

    protected $volume_data = [];

    protected $volume_limit = 500;

    protected $page = 1;

    protected $limit = 300;

    protected $year;

    private $file_name;

    private $log_title;

    private $start = '2016-01-01';

    private $end = '2018-12-31';

    private $ori_content = '';

    private $content = '';

    private $last_code = 0;

    private $Stock = '';

    private $not_read = '';

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

    protected function setTradeDate()
    {
    }

    protected function set_volume($date)
    {
        $end = $date;

        $start = Holiday_logic::getInstance()->get_work_date($before_days = $this->rule_avg_volume_days, $now_date = $date, $type = 1);

        $this->volume_data = $this->Stock->get_stock_data_by_date_range($start, $end, $this->code);

        $this->volume_data = collect($this->volume_data)->map(function ($item) {
            return collect($item)->pluck('volume')->avg();
        })->shift();
    }

    protected function count()
    {
        //  建立檔案

        $this->create_file();

        //  設定變數

        $this->set();

        //  過程

        $this->process();

        //  檢查

        if ($this->checkError() === true) {
            return false;
        }

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

        $this->Tech = TechnicalAnalysis_logic::getInstance();

        $this->Stock = Stock_logic::getInstance();

        //  股價為 -- 的項目計算上有誤差，撈出來排除掉

        $this->set_filter_code();
    }

    private function set_filter_code()
    {
        $this->not_read = $this->Stock->get_stock_by_none_price()->pluck('code')->toArray();
    }

    private function setStockData()
    {
        try {
            $stockInfo = $this->Stock->get_all_stock_info();

            $this->stock_id = $stockInfo->pluck('id')->forPage($this->page, $this->limit)->toArray();

            $this->Stock_data = $this->Stock->get_stock_data($this->stock_id, $this->start, $this->end);

            if (empty($this->Stock_data)) {
                throw new \Exception('無股價資料');
            }

            $this->Stock_data = $this->Stock_data->map(function ($item, $stock_id) {
                $data = collect($item)->mapWithKeys(function ($item) {
                    return [$item->data_date => $this->except($item->highest + $item->lowest, 2)];
                })->toArray();

                return $data;
            })->toArray();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return '';
    }

    private function setPercentBData()
    {
        $this->Tech_data = $this->Tech->get_data_by_year($this->year, $this->stock_id);
    }

    private function setSellBuyPercentData()
    {
        $this->sellBuyPercent = SellBuyPercent_logic::getInstance()->get_data_by_year($this->year, $this->stock_id)->groupBy('stock_id')
            ->map(function ($item) {
                return collect($item)->filter(function ($item) {
                    return $item->result > 0;
                })->mapWithKeys(function ($item) {
                    return [$item->data_date => $item->result];
                })->toArray();
            })
            ->toArray();
    }

    private function count_avg()
    {
        $sum = array_sum($this->Stock_data);

        $cnt = \count($this->Stock_data);

        return round($sum / $cnt, 2);
    }

    private function error_filter()
    {
        try {
            // //  價格太低的濾掉

            // if ( $this->count_avg() < 20 )
            // {
            //     throw new \Exception( "均價低於20" );
            // }

            //  沒資料

            if (empty($this->buy_date)) {
                throw new \Exception('沒有符合的percentB資料');
            }

            if (empty($this->sell_date)) {
                throw new \Exception('沒有賣出資料');
            }

            collect($this->sell_date)->flatten(1)->map(function ($item, $key) {
                if (!isset($this->Stock_data[$item['stock_id']][$item['data_date']])) {
                    throw new \Exception('賣出日期比對失敗');
                }
            });

            collect($this->buy_date)->flatten(1)->map(function ($item, $key) {
                if (!isset($this->Stock_data[$item['stock_id']][$item['data_date']])) {
                    throw new \Exception('買進日期比對失敗');
                }
            });
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return '';
    }

    private function format()
    {
        $this->result = collect($this->sell_date)->flatten(1)->filter(function ($item) {
            $stock_id = $item['stock_id'];
            $sell_date = $item['data_date'];

            return isset($this->Stock_data[$stock_id][$sell_date]) && !empty($this->Stock_data[$stock_id][$sell_date]);
        })->map(function ($item, $key) {
            $stock_id = $item['stock_id'];

            $buy_date = $this->buy_date[$item['stock_id']][$key]['data_date'];
            $sell_date = $item['data_date'];

            $buy_fee = ceil($this->Stock_data[$stock_id][$buy_date] * 1000 * 0.001425);
            $sell_fee = ceil($this->Stock_data[$stock_id][$sell_date] * 1000 * 0.001425);
            $tax = ceil($this->Stock_data[$stock_id][$sell_date] * 1000 * 0.003);
            $diff = round($this->Stock_data[$stock_id][$sell_date] - $this->Stock_data[$stock_id][$buy_date], 2);

            return implode(',', [
                'code' => $this->code,
                'buy_date' => $buy_date,
                'buy_percentB' => $this->buy_date[$stock_id][$key]['percentB'],
                'buy_price' => $this->Stock_data[$stock_id][$buy_date],
                'buy_fee' => $buy_fee,
                'sell_date' => $sell_date,
                'sell_percentB' => $item['percentB'],
                'sell_price' => $this->Stock_data[$stock_id][$sell_date],
                'sell_fee' => $sell_fee,
                'tax' => $tax,
                'diff' => $diff,
                'profit' => ceil(($diff * 1000) - $buy_fee - $sell_fee - $tax),
                'sellBuyPercentAtBuy' => isset($this->sellBuyPercent[$stock_id][$buy_date]) ? $this->sellBuyPercent[$stock_id][$buy_date] : 0,
                'sellBuyPercentAtSell' => isset($this->sellBuyPercent[$stock_id][$sell_date]) ? $this->sellBuyPercent[$stock_id][$sell_date] : 0,
                'error' => 'Correct',
            ]);
        });
    }

    private function process()
    {
        $this->insert_content = $this->Stock->get_all_stock_info()->filter(function ($item) {
            return $item->code > $this->last_code && !\in_array($item->code, $this->not_read, true);
        })->forPage($this->page, $this->limit)->map(function ($item) {
            try {
                $this->code = $item->code;

                $error = $this->setStockData();

                if (!empty($error)) {
                    throw new \Exception($error);
                }

                $this->setPercentBData();

                $this->setSellBuyPercentData();

                $this->setTradeDate();

                $error = $this->error_filter();

                if (!empty($error)) {
                    throw new \Exception($error);
                }

                $this->format();
            } catch (\Exception $e) {
                dd($e);

                return collect([implode(',', [
                    'code' => $item->code,
                    'buy_date' => '-',
                    'buy_percentB' => '-',
                    'buy_price' => 0,
                    'buy_fee' => 0,
                    'sell_date' => '-',
                    'sell_percentB' => '-',
                    'sell_price' => 0,
                    'sell_fee' => 0,
                    'tax' => 0,
                    'diff' => 0,
                    'profit' => 0,
                    'sellBuyPercentAtBuy' => 0,
                    'sellBuyPercentAtSell' => 0,
                    'error' => $e->getMessage(),
                ])]);
            }

            return $this->result;
        });
    }

    private function checkError()
    {
        try {
            $data = $this->insert_content->map(function ($item) {
                $item = $item->map(function ($item) {
                    $tmp = explode(',', $item);

                    return $tmp[1];
                });

                return $item;
            })->toArray();

            foreach ($data as $row) {
                foreach ($row as $key => $date) {
                    if (isset($row[$key - 1]) && strtotime($date) <= strtotime($row[$key - 1])) {
                        throw new \Exception(true);
                    }
                }
            }
        } catch (\Exception $e) {
            return true;
        }

        return false;
    }

    private function record()
    {
        $this->insert_content->map(function ($item) {
            collect($item)->map(function ($item) {
                $this->ori_content .= $item."\n";
                $this->new_content .= $item."\n";
            });
        });

        Storage::put($this->file_name, $this->ori_content);
    }
}
