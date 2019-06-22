<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class SellBuyPercent
{

    private $stock_info_table = 'stock_info';
    private $stock_data_table = 'stock_data';
    private $table = 'sell_buy_percent';

	public function add_sell_buy_percent_data( $data )
	{

		$result = DB::table($this->table)->insert($data);

		return $result;

	}

	public function edit_sell_buy_percent_data( $data, $id )
	{

		$result = DB::table($this->table)->where('id', $id)->update($data);

		return $result;

	}

	public function get_statistics( $stock_id )
	{

		$result = DB::table($this->stock_data_table)
					->select(
                        $this->stock_data_table.'.id as data_id',
                        $this->stock_data_table.'.data_date',
                        $this->stock_data_table.'.volume',
                        $this->stock_data_table.'.open',
                        $this->stock_data_table.'.highest',
                        $this->stock_data_table.'.lowest',
                        $this->stock_data_table.'.close'
					)
					->where( $this->stock_data_table . '.stock_id', $stock_id )
					->orderBy( $this->stock_data_table.'.data_date' )
					->get();

		return $result;

	}

	public function get_first_data_time()
	{

		$result = DB::table($this->stock_data_table)
					->select(
                        $this->stock_data_table.'.stock_id',
						DB::raw('min(data_date) as data_date')
					)
					->groupBy( $this->stock_data_table.'.stock_id' )
					->get();

		return $result;

	}

	public function get_last_update_time()
	{

		$result = DB::table($this->stock_data_table)
					->select(
                        $this->stock_data_table.'.stock_id',
						DB::raw('max(data_date) as data_date')
					)
					->groupBy( $this->stock_data_table.'.stock_id' )
					->get();

		return $result;

	}

	public function get_last_stock_code( $last_working_date )
	{

		$result = DB::table($this->table)->where( "data_date", $last_working_date )->orderBy( $this->table.'.code', "desc" )->first();

		return $result;

	}

    public function get_data( $code )
    {

        $result = DB::table($this->table)->where( "code", $code )->get();

        return $result;

    }

    public function get_data_by_range( $start, $end )
    {

        $result = DB::table($this->table)->whereBetween( "data_date", [$start, $end] )->get();

        return $result;

    }

    public function get_today_result( $stock_id, $date )
    {

        $result = DB::table($this->table)
            ->select(
                'code',
                'result'
            )
            ->whereIn("stock_id", $stock_id)
            ->where("data_date", $date)
            ->get();

        return $result;

    }

    public static function getInstance()
    {

        return new self;

    }

}
