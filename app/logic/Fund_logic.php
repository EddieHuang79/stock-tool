<?php

namespace App\logic;

use App\model\Fund;
use Illuminate\Support\Facades\Storage;

class Fund_logic
{
    private $start_year = 2016;

    private $fund_parents_dir = 'fund';

    public function save_from_file(int $year)
    {
        $idCodeMapping = Stock_logic::getInstance()->mapping_code_and_id();
        $codeIdMapping = array_flip($idCodeMapping);

        $exist_code = collect($idCodeMapping)->values()->toArray();

        // 上市

        $file_path = $this->fund_parents_dir.'/'.$year.'/1';

        $file_list = Storage::allFiles($file_path);

        $latest = $this->get_lastest_date($year, 1);
        $latest = !empty($latest) ? $latest : $year.'0101';
        // $latest = '20190101';

        $this->add_fund_data1($file_list, $file_path, $exist_code, $codeIdMapping, $year, $latest);

        // 上櫃

        $file_path = $this->fund_parents_dir.'/'.$year.'/2';

        $file_list = Storage::allFiles($file_path);

        $latest = $this->get_lastest_date($year, 2);
        $latest = !empty($latest) ? $latest : $year.'0101';

        $this->add_fund_data2($file_list, $file_path, $exist_code, $codeIdMapping, $year, $latest);
    }

    public function add(array $data, int $year)
    {
        Fund::getInstance()->add($data, $year);
    }

    public function get_lastest_date(int $year, int $type)
    {
        return Fund::getInstance()->get_lastest_date($year, $type)->pluck('data_date')->shift();
    }

    public function get(int $year, array $filter_stock_id)
    {
        return Fund::getInstance()->get($year, $filter_stock_id)->groupBy('stock_id')->mapWithKeys(function ($item, $stock_id) {
            $data = $item->mapWithKeys(function ($item) {
                return [$item->data_date => collect($item)->toArray()];
            })->toArray();

            return [$stock_id => $data];
        })->toArray();
    }

    public static function getInstance()
    {
        return new self();
    }

    private function add_fund_data1(array $file_list, string $file_path, array $exist_code, array $codeIdMapping, int $year, string $latest)
    {
        $data = collect($file_list)->filter(function ($file) use ($file_path, $latest) {
            $file_name = str_replace($file_path.'/', '', $file);
            $date = str_replace('.csv', '', $file_name);

            return strtotime($date) > strtotime($latest);
        })->map(function ($file) use ($file_path, $exist_code, $codeIdMapping, $year, $latest) {
            $file_name = str_replace($file_path.'/', '', $file);
            $date = str_replace('.csv', '', $file_name);

            $file_data = Storage::get($file);

            $content = explode("\r\n", $file_data);

            $content = collect($content)->map(function ($item) use ($date) {
                $item = str_replace('="', '', $item);
                $item = str_replace('","', '@', $item);
                $item = str_replace(',', '', $item);
                $item = str_replace('=', '', $item);
                $item = str_replace('"', '', $item);
                $item = explode('@', $item);

                for ($i = 2; $i <= 15; ++$i) {
                    $item[$i] = isset($item[$i]) ? (int) ($item[$i]) : 0;
                }
                $item[99] = date('Y-m-d', strtotime($date));

                return $item;
            })->filter(function ($item) use ($exist_code) {
                return \in_array((int) $item[0], $exist_code, true);
            })->map(function ($item) use ($codeIdMapping) {
                return [
                    'code' => (int) $item[0],
                    'stock_id' => $codeIdMapping[(int) $item[0]],
                    'data_date' => $item[99],
                    'type' => 1,
                    'foreign_investor_buy' => $item[2],
                    'foreign_investor_sell' => $item[3],
                    'foreign_investor_total' => $item[4],
                    'investment_trust_buy' => $item[5],
                    'investment_trust_sell' => $item[6],
                    'investment_trust_total' => $item[7],
                    'dealer_buy' => $item[9],
                    'dealer_sell' => $item[10],
                    'dealer_total' => $item[11],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            })->values()->toArray();

            $this->add($content, $year);
        });
    }

    private function add_fund_data2(array $file_list, string $file_path, array $exist_code, array $codeIdMapping, int $year, string $latest)
    {
        $data = collect($file_list)->filter(function ($file) use ($file_path, $latest) {
            $file_name = str_replace($file_path.'/', '', $file);
            $date = str_replace('.csv', '', $file_name);

            return strtotime($date) > strtotime($latest);
        })->map(function ($file) use ($file_path, $exist_code, $codeIdMapping, $year, $latest) {
            $file_name = str_replace($file_path.'/', '', $file);
            $date = str_replace('.csv', '', $file_name);

            $file_data = Storage::get($file);

            $content = explode("\r\n", $file_data);

            $content = collect($content)->map(function ($item) use ($date) {
                $item = explode(',', $item);
                $item[99] = date('Y-m-d', strtotime($date));

                return $item;
            })->filter(function ($item) use ($exist_code) {
                return \in_array((int) $item[0], $exist_code, true);
            })->map(function ($item) use ($codeIdMapping) {
                return [
                    'code' => (int) $item[0],
                    'stock_id' => $codeIdMapping[(int) $item[0]],
                    'data_date' => $item[99],
                    'type' => 2,
                    'foreign_investor_buy' => (int) $item[2],
                    'foreign_investor_sell' => (int) $item[3],
                    'foreign_investor_total' => (int) $item[4],
                    'investment_trust_buy' => (int) $item[5],
                    'investment_trust_sell' => (int) $item[6],
                    'investment_trust_total' => (int) $item[7],
                    'dealer_buy' => (int) $item[9] + (int) $item[12],
                    'dealer_sell' => (int) $item[10] + (int) $item[13],
                    'dealer_total' => (int) $item[8],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            })->values()->toArray();

            $this->add($content, $year, 2);
        });
    }
}
