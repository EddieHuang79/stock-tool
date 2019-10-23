<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class TechnicalAnalysis
{

	private $table = 'technical_analysis';

    private $data_table = 'stock_data';

    private $info_table = 'stock_info';

	public function add_data( $data )
	{

		$result = DB::table($this->table)->insert($data);

		return $result;

	}

	public function update_data( $data, $id )
	{

		$result = DB::table($this->table)->where("id", $id)->update($data);

		return $result;

	}

	public function get_data( $stock_id, $start, $end )
	{

		$result = DB::table( $this->table )
					->where( $this->table . '.stock_id', $stock_id );

		$result = !empty($start) && !empty($end) ? $result->whereBetween( $this->table . ".data_date", [$start, $end] ) : $result ;

		$result = $result->orderby( $this->table . '.data_date' )->get();

		return $result;

	}

	public function count_cross_data( $option )
	{

		$result = DB::table( $this->table )->where( $this->table . '.id', '!=', 'null' );

		$result = !empty($option["start"]) && !empty($option["end"]) ? $result->wherebetween( $this->table . ".data_date", [ $option["start"], $option["end"] ] ) : $result ;

		$result = $result->orderby( $this->table . '.data_date' )->get();

		return $result;

	}


	// 建立空陣列資料

	public function create_init_data()
	{

	    //  先取得最後一筆id

	    $data = DB::table( $this->table )->select("stock_data_id")->orderBy( "stock_data_id", "desc" )->limit(1)->first();

	    $last_id = $data->stock_data_id;

		$result = DB::table( $this->data_table )
					->leftjoin( $this->info_table, $this->info_table . '.id', $this->data_table . '.stock_id' )
					->select(
						$this->info_table . '.code',
						$this->data_table . '.id as stock_data_id',
						$this->data_table . '.stock_id',
						$this->data_table . '.data_date'
					)
                    ->where( $this->data_table . '.id', ">", $last_id )
					->orderby( $this->data_table . '.id' )
					->limit(1000)
					->get();

		return $result;

	}


	// 找出要計算的前10支股票代號

	public function get_stock_tech_update_date_v2()
	{

		$result = DB::table( $this->table )
					->select(
						$this->table . '.stock_id',
						$this->table . '.code'
					)
					->where( "step", 0 )
					->groupby( 'code', 'stock_id' )->orderby( $this->table . '.code' )->limit(20)->get();

		return $result;

	}

    public function get_data_by_range( $start, $end, $code )
    {

        $result = DB::table( $this->table )
            ->select(
                "code",
                "data_date",
                "RSI5",
                "MA20",
                "upperBand",
                "lowerBand",
                "percentB",
                "bandwidth"
            )
            ->whereBetween( $this->table . '.data_date', [ $start, $end ] )
            ->where("step", 4);

        $result = !empty($code) ? $result->where("code", $code) : $result;

        $result = $result->orderby( $this->table . '.code' )
                    ->orderby( $this->table . '.data_date' )
                    ->get();

        return $result;

    }

    public function get_today_percentB( $stock_id, $date )
    {

        $result = DB::table($this->table)
            ->select(
                'code',
                'percentB'
            )
            ->whereIn("stock_id", $stock_id)
            ->where("data_date", $date)
            ->get();

        return $result;

    }

	// 回傳自己

	public static function getInstance()
	{

		return new self ;

	}

}
