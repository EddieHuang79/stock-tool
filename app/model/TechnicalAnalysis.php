<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class TechnicalAnalysis
{
    private $table = 'technical_analysis';

    private $data_table = 'stock_data';

    private $info_table = 'stock_info';

    public function add_data($data)
    {
        $result = DB::table($this->table)->insert($data);

        return $result;
    }

    public function update_data($data, $id)
    {
        $result = DB::table($this->table)->where('id', $id)->update($data);

        return $result;
    }

    public function update_history_data(array $data, int $id, int $year, int $stock_id)
    {
        $result = DB::table($this->table.'_'.$year)->where('id', $id)->update($data);
        $result = DB::table($this->table.'_'.$year)->where('stock_id', $stock_id)->where('step', 0)->update(['step' => 4]);

        return $result;
    }

    public function get_data(array $stock_id, string $start, string $end)
    {
        return DB::table($this->table)
                ->whereIn($this->table.'.stock_id', $stock_id)
                ->whereBetween($this->table.'.data_date', [$start, $end])
                ->orderby($this->table.'.data_date')
                ->get();
    }

    public function count_cross_data($option)
    {
        $result = DB::table($this->table)->where($this->table.'.id', '!=', 'null');

        $result = !empty($option['start']) && !empty($option['end']) ? $result->wherebetween($this->table.'.data_date', [$option['start'], $option['end']]) : $result;

        $result = $result->orderby($this->table.'.data_date')->get();

        return $result;
    }

    // 建立空陣列資料

    public function create_init_data()
    {
        //  先取得最後一筆id

        $data = DB::table($this->table)->select('stock_data_id')->orderBy('stock_data_id', 'desc')->limit(1)->first();

        $last_id = $data->stock_data_id;

        $result = DB::table($this->data_table)
                    ->leftjoin($this->info_table, $this->info_table.'.id', $this->data_table.'.stock_id')
                    ->select(
                        $this->info_table.'.code',
                        $this->data_table.'.id as stock_data_id',
                        $this->data_table.'.stock_id',
                        $this->data_table.'.data_date'
                    )
                    ->where($this->data_table.'.id', '>', $last_id)
                    ->orderby($this->data_table.'.id')
                    ->limit(1000)
                    ->get();

        return $result;
    }

    // 找出要計算的前10支股票代號

    public function get_stock_tech_update_date_v2()
    {
        $result = DB::table($this->table)
                    ->select(
                        $this->table.'.stock_id',
                        $this->table.'.code'
                    )
                    ->where('step', 0)
                    ->groupby('code', 'stock_id')->orderby($this->table.'.code')->limit(200)->get();

        return $result;
    }

    // 找出要計算的前10支股票代號

    public function get_history_stock_tech_update_date_v2(int $year)
    {
        $table = $this->table.'_'.$year;

        $result = DB::table($table)
                    ->select(
                        $table.'.stock_id',
                        $table.'.code'
                    )
                    ->where('step', 0)
                    ->groupby('code', 'stock_id')->orderby($table.'.code')->limit(170)->get();

        return $result;
    }

    public function get_data_by_range($start, $end, $code)
    {
        $result = DB::table($this->table)
            ->select(
                'code',
                'data_date',
                'RSI5',
                'MA20',
                'upperBand',
                'lowerBand',
                'percentB',
                'bandwidth'
            )
            ->whereBetween($this->table.'.data_date', [$start, $end])
            ->where('step', 4);

        $result = !empty($code) ? $result->where('code', $code) : $result;

        $result = $result->orderby($this->table.'.code')
                    ->orderby($this->table.'.data_date')
                    ->get();

        return $result;
    }

    public function get_today_percentB($stock_id, $date)
    {
        $result = DB::table($this->table)
            ->select(
                'code',
                'percentB'
            )
            ->whereIn('stock_id', $stock_id)
            ->where('data_date', $date)
            ->get();

        return $result;
    }

    public function get_data_by_year(int $year, array $stock_id)
    {
        $result = DB::table($this->table.'_'.$year)
            ->select(
                'stock_id',
                'code',
                'data_date',
                'bandwidth',
                'percentB'
            )
            ->whereIn('stock_id', $stock_id)
            ->get();

        return $result;
    }

    public function get_history_data(array $stock_id, int $year)
    {
        $table = $this->table.'_'.$year;

        return DB::table($table)
                ->whereIn($table.'.stock_id', $stock_id)
                ->orderby($table.'.data_date')
                ->get();
    }

    // 回傳自己

    public static function getInstance()
    {
        return new self();
    }
}
