<?php

namespace App\logic;

use App\model\TechnicalAnalysis;
use App\Traits\Mathlib;
use App\Traits\SchemaFunc;
use Illuminate\Support\Facades\DB;

class TechnicalAnalysis_logic
{
    use SchemaFunc;
    use Mathlib;

    // type - 1: RSV, 2: K, 3: D, 4: RSI5, 5: RSI10, 6: DIFF, 7: MACD, 8: OSC

    public function insert_format($data)
    {
        $result = [];

        if (!empty($data) && \is_array($data)) {
            $result = [
                'stock_id' => isset($data['stock_id']) ? (int) ($data['stock_id']) : '',
                'stock_data_id' => isset($data['stock_data_id']) ? (int) ($data['stock_data_id']) : '',
                'code' => isset($data['code']) ? (int) ($data['code']) : '',
                'data_date' => isset($data['data_date']) ? $data['data_date'] : '',
                'RSV' => isset($data['RSV']) ? (float) ($data['RSV']) : 0.0,
                'K9' => isset($data['K9']) ? (float) ($data['K9']) : 0.0,
                'D9' => isset($data['D9']) ? (float) ($data['D9']) : 0.0,
                'RSI5' => isset($data['RSI5']) ? (float) ($data['RSI5']) : 0.0,
                'RSI10' => isset($data['RSI10']) ? (float) ($data['RSI10']) : 0.0,
                'DIFF' => isset($data['DIFF']) ? (float) ($data['DIFF']) : 0.0,
                'MACD' => isset($data['MACD']) ? (float) ($data['MACD']) : 0.0,
                'OSC' => isset($data['OSC']) ? (float) ($data['OSC']) : 0.0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    // 		寫入資料

    public function add_data($data)
    {
        $result = false;

        if (!empty($data) && \is_array($data)) {
            $result = TechnicalAnalysis::getInstance()->add_data($data);
        }

        return $result;
    }

    // 		更新資料

    public function update_data($data, $id)
    {
        $result = false;

        if (!empty($data) && \is_array($data) && !empty($id) && \is_int($id)) {
            $result = TechnicalAnalysis::getInstance()->update_data($data, $id);
        }

        return $result;
    }

    // 		更新資料

    public function update_history_data(array $data, int $id, int $year, int $stock_id)
    {
        return TechnicalAnalysis::getInstance()->update_history_data($data, $id, $year, $stock_id);
    }

    // 		取得資料

    public function get_data(array $stock_id, string $start, string $end)
    {
        return TechnicalAnalysis::getInstance()->get_data($stock_id, $start, $end)->groupBy('stock_id');
    }

    // 		取得歷史資料

    public function get_history_data(array $stock_id, int $year)
    {
        return TechnicalAnalysis::getInstance()->get_history_data($stock_id, $year)->groupBy('stock_id');
    }

    // 計算交錯信號

    /*

        code: 股票代號
        type: 1: KD, 2: RSI, 3: MACD
        start: 偵測開始區間
        end: 偵測結束區間

    */

    public function get_cross_sign($type, $start, $end)
    {
        $result = [];

        if (!empty($type) && \is_int($type) && !empty($start) && \is_string($start) && !empty($end) && \is_string($end)) {
            $option = [
                'type' => [],
                'start' => $start,
                'end' => $end,
            ];

            switch ($type) {
                // KD

                case 1:

                    $key1 = 'K9';

                    $key2 = 'D9';

                    break;
                // RSI

                case 2:

                    $key1 = 'RSI5';

                    $key2 = 'RSI10';

                    break;
                // MACD

                case 3:

                    $key1 = 'DIFF';

                    $key2 = 'MACD';

                    break;
            }

            $data = $this->count_cross_data($option);

            $tmp = collect($data)->mapToGroups(function ($item, $key) {
                return [$item->code => get_object_vars($item)];
            })->toArray();

            // status: 若是A值比B值大，給1，反之給2

            foreach ($tmp as $code => $item) {
                $cross_sign = [
                    'gold_cross' => [],
                    'dead_cross' => [],
                ];

                $status = $item[0][$key1] > $item[0][$key2] ? 1 : 2;

                foreach ($item as $row) {
                    switch ($status) {
                        // 初始值A > B，因此當出現B > A時回報死叉

                        case 1:

                            if ($row[$key1] < $row[$key2]) {
                                $cross_sign['dead_cross'][] = [
                                    'date' => $row['data_date'],
                                    'value1' => $row[$key1],
                                    'value2' => $row[$key2],
                                ];

                                $status = 2;
                            }

                            break;
                        // 初始值B > A，因此當出現B > A時回報金叉

                        case 2:

                            if ($row[$key1] > $row[$key2]) {
                                $cross_sign['gold_cross'][] = [
                                    'date' => $row['data_date'],
                                    'value1' => $row[$key1],
                                    'value2' => $row[$key2],
                                ];

                                $status = 1;
                            }

                            break;
                    }
                }

                $result[$code] = $cross_sign;
            }
        }

        return $result;
    }

    // 取得通知範圍

    /*

        距離今日在5個工作日內(懶得判斷假日)
        金叉通知: value1 & value2 < 20才通知, type: 1
        死叉通知: value1 & value2 > 80才通知, type: 2

    */

    public function is_notice_data($data)
    {
        $result = [
            'type' => 1,
            'status' => false,
            'data' => [],
        ];

        $diff_limit = 86400 * 5;

        foreach ($data['gold_cross'] as $row) {
            if ($row['value1'] <= 20 && $row['value2'] <= 20 && time() - strtotime($row['date']) <= $diff_limit) {
                $result = [
                    'type' => 1,
                    'status' => true,
                    'data' => $row,
                ];
            }
        }

        foreach ($data['dead_cross'] as $row) {
            if ($row['value1'] >= 80 && $row['value2'] >= 80 && time() - strtotime($row['date']) <= $diff_limit) {
                $result = [
                    'type' => 2,
                    'status' => true,
                    'data' => $row,
                ];
            }
        }

        return $result;
    }

    // 		上引線

    public function hasUpperShadows($data)
    {
        $result = [
            'status' => false,
            'data' => '',
        ];

        if (!empty($data) && \is_object($data)) {
            $parent = (float) ($data->close) > (float) ($data->open) ? (float) ($data->close) : (float) ($data->open);

            $child = (float) ($data->highest) - $parent;

            $value = $this->except($child, $parent);

            $result = [
                'status' => $value > 0.05,
                'data' => $value,
            ];
        }

        return $result;
    }

    // 		下引線

    public function hasLowerShadows($data)
    {
        $result = [
            'status' => false,
            'data' => '',
        ];

        if (!empty($data) && \is_object($data)) {
            $parent = (float) ($data->close) > (float) ($data->open) ? (float) ($data->close) : (float) ($data->open);

            $child = $parent - (float) ($data->lowest);

            $value = $this->except($child, $parent);

            $result = [
                'status' => $value > 0.03,
                'data' => $value,
            ];
        }

        return $result;
    }

    // 		建立初始資料

    public function create_init_data()
    {
        Record_logic::getInstance()->write_operate_log($action = 'create_init_data', $content = 'in process');

        DB::raw('START TRANSACTION');

        $data = TechnicalAnalysis::getInstance()->create_init_data()->map(function ($item) {
            return [
                'stock_id' => $item->stock_id,
                'stock_data_id' => $item->stock_data_id,
                'code' => $item->code,
                'data_date' => $item->data_date,
                'RSV' => 0.00,
                'K9' => 0.00,
                'D9' => 0.00,
                'RSI5' => 0.00,
                'RSI10' => 0.00,
                'DIFF' => 0.00,
                'MACD' => 0.00,
                'OSC' => 0.00,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        })->toArray();

        $this->add_data($data);

        DB::raw('COMMIT');

        return true;
    }

    //      以區間來取得資料

    public function get_data_by_range($start, $end, $code = '')
    {
        return TechnicalAnalysis::getInstance()->get_data_by_range($start, $end, $code);
    }

    //      取得技術指標更新日期

    public function get_history_stock_tech_update_date_v2(int $year)
    {
        return TechnicalAnalysis::getInstance()->get_history_stock_tech_update_date_v2($year);
    }

    //      取得技術指標更新日期

    public function get_stock_tech_update_date_v2()
    {
        return TechnicalAnalysis::getInstance()->get_stock_tech_update_date_v2();
    }

    public function get_today_percentB($stock_id = [], $date)
    {
        return TechnicalAnalysis::getInstance()->get_today_percentB($stock_id, $date);
    }

    public function get_data_by_year(int $year, array $stock_id): array
    {
        return TechnicalAnalysis::getInstance()->get_data_by_year($year, $stock_id)->groupBy('stock_id')
            ->sortBy('data_date')->map(function ($item) {
                return $item->mapWithKeys(function ($item) {
                    return [$item->data_date => $item];
                });
            })->toArray();
    }

    public static function getInstance()
    {
        return new self();
    }

    // 取得資料 > for指標交叉使用

    private function count_cross_data($option)
    {
        $result = [];

        if (!empty($option) && \is_array($option)) {
            $result = TechnicalAnalysis::getInstance()->count_cross_data($option);
        }

        return $result;
    }
}
