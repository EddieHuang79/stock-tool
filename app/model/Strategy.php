<?php

namespace App\Model;

use Illuminate\Support\Facades\DB;

class Strategy
{
    private $table = 'strategy';
    private $rel_table = 'strategy_rel';

    public function get_list()
    {
        $result = DB::table($this->table)->get();

        return $result;
    }

    public function get_rel_id($type, $strategy_id)
    {
        $result = DB::table($this->rel_table)
                ->select($this->rel_table.'.rel_id')
                ->where($this->rel_table.'.type', $type)
                ->where($this->rel_table.'.strategy_id', $strategy_id)
                ->get();

        return $result;
    }

    public function add_data($data)
    {
        $result = DB::table($this->table)->insertGetId($data);

        return $result;
    }

    public function add_rel_data($data)
    {
        $result = DB::table($this->rel_table)->insert($data);

        return $result;
    }

    public static function getInstance()
    {
        return new self();
    }
}
