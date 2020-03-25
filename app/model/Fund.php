<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Fund
{
    protected $table = 'fund';

    public function add(array $data, int $year)
    {
        $table = $year === (int) date('Y') ? $this->table : $this->table.'_'.$year;

        return DB::table($table)->insert($data);
    }

    public function get_lastest_date(int $year, int $type)
    {
        $result = DB::table($this->table.'_'.$year)
            ->select(
                'data_date'
            )
            ->where('type', $type)
            ->orderBy('data_date', 'DESC')
            ->limit(1)
            ->get();

        return $result;
    }

    public function get(int $year, array $filter_stock_id = [])
    {
        $result = DB::table($this->table.'_'.$year)
                ->select(
                    'code',
                    'stock_id',
                    'data_date',
                    'investment_trust_total'
                );
        $result = !empty($filter_stock_id) ? $result->whereIn('stock_id', $filter_stock_id) : $result;
        $result = $result->orderBy('data_date')->get();

        return $result;
    }

    public static function getInstance()
    {
        return new self();
    }
}
