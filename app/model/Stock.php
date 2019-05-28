<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Stock
{

    protected $table = 'stock_info';
    protected $data_table = 'stock_data';
    protected $primaryKey = 'id';

	public function get_list( )
	{

		$result = DB::table($this->table)->get();

		return $result;

	}

	public function get_stock( $code )
	{

		$result = DB::table($this->table)->where( 'code', $code )->first();

		return $result;

	}


	public function get_stock_list( )
	{

		$result = DB::table($this->table)->get();

		return $result;

	}

	public function add_stock_data( $data )
	{

		$result = DB::table($this->data_table)->insert($data);

		return $result;

	}

	public function get_stock_data( $stock_id )
	{

		$result = DB::table($this->data_table)->where( 'stock_id', $stock_id )->orderBy( $this->data_table . '.data_date' )->get();

		return $result;

	}

	public function get_all_stock_data( $type = 1, $sub_type = 1 )
	{

		$start_code = $sub_type . '000';
		$end_code = $sub_type . '999';

		$result = DB::table($this->data_table)
				->leftJoin($this->table, $this->table.'.id', '=', $this->data_table.'.stock_id')
				->select(
                    $this->table . '.code',
                    $this->data_table . '.data_date'
				);
		$result = $type === 2 ? $result->whereBetween( $this->data_table . '.data_date', [ date("Y-m-01"), date("Y-m-t") ] ) : $result ;
		$result = $type === 1 ? $result->whereBetween( $this->table . '.code', [ (int)$start_code  , (int)$end_code ] ) : $result ;
		$result = $result->groupBy( $this->data_table . '.data_date', $this->table.'.code' )
				->orderBy( $this->data_table . '.data_date' )
				->get();

		return $result;

	}


	public function get_all_stock_info()
	{

		$result = DB::table($this->table)->get();

		return $result;

	}


	public function get_all_stock_data_id()
	{

		$result = DB::table($this->data_table)
				->leftJoin($this->table, $this->table.'.id', '=', $this->data_table.'.stock_id')
				->select(
                    $this->table . '.code',
                    $this->data_table . '.id as stock_data_id'
				)
				->groupBy( $this->data_table . '.id', $this->table.'.code' )
				->orderBy( $this->data_table . '.id' )
				->get();

		return $result;

	}

	public function get_stock_data_by_date_range( $start, $end, $code = '' )
	{

		$result = DB::table($this->data_table)
				->leftJoin($this->table, $this->table.'.id', '=', $this->data_table.'.stock_id')
				->select(
					$this->table . '.code',
					$this->data_table . '.id',
					$this->data_table . '.data_date',
					$this->data_table . '.volume',
					$this->data_table . '.open',
					$this->data_table . '.highest',
					$this->data_table . '.lowest',
					$this->data_table . '.close'
				)
				->whereBetween( $this->data_table . '.data_date', [ $start, $end ] );

		$result = !empty($code) ? $result->where( "code", $code ) : $result ;

		$result = $result->orderBy( $this->data_table . '.data_date' )->get();

		return $result;

	}


	public function get_start_trade_date( $stock_id )
	{

		$result = DB::table($this->data_table)->select( "data_date" )->where( 'stock_id', $stock_id )->orderBy( "data_date" )->first();

		return $result;

	}

	public function get_stock_by_none_price()
    {

        $result = DB::table($this->data_table)
            ->leftJoin($this->table, $this->table.'.id', '=', $this->data_table.'.stock_id')
            ->select( $this->table . ".code" )
            ->where( 'close', '--' )
            ->groupBy($this->data_table.'.stock_id')
            ->get();

        return $result;

    }

    public static function getInstance()
    {

        return new self;

    }

}
