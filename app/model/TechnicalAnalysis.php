<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class TechnicalAnalysis
{

	protected $table = 'technical_analysis';

	protected $data_table = 'stock_data';

	protected $info_table = 'stock_info';

	public static function add_data( $data )
	{

		$_this = new self;

		$result = DB::table($_this->table)->insert($data);

		return $result;

	}


	public static function get_data( $type, $id )
	{

		$_this = new self;

		$result = DB::table( $_this->table )
					->leftJoin( $_this->data_table, $_this->table . '.stock_data_id', $_this->data_table . '.id' )
					->select( $_this->table . '.*', $_this->data_table . '.data_date' )
					->where( $_this->table . '.type', $type );

		$result = !empty($id) ? $result->whereIn( $_this->table . ".stock_data_id", $id ) : $result ;
		
		$result = $result->orderBy( $_this->data_table . '.data_date' )->get();

		return $result;

	}


	public static function count_cross_data( $option )
	{

		$_this = new self;

		$result = DB::table( $_this->info_table )
					->leftJoin( $_this->data_table, $_this->info_table . '.id', $_this->data_table . '.stock_id' )
					->leftJoin( $_this->table, $_this->table . '.stock_data_id', $_this->data_table . '.id' )
					->select( $_this->table . '.*', $_this->data_table . '.data_date', $_this->info_table . '.code' )
					->where( $_this->table . '.id', '!=', 'null' );

		$result = !empty($option["type"]) ? $result->whereIn( $_this->table . ".type", $option["type"] ) : $result ;
		$result = !empty($option["start"]) && !empty($option["end"]) ? $result->whereBetween( $_this->data_table . ".data_date", [ $option["start"], $option["end"] ] ) : $result ;
		$result = $result->orderBy( $_this->data_table . '.data_date' )->get();

		return $result;

	}


	public static function get_count_data( $type )
	{

		$_this = new self;

		$result = DB::table( $_this->table )
					->leftJoin( $_this->data_table, $_this->table . '.stock_data_id', $_this->data_table . '.id' )
					->leftJoin( $_this->info_table, $_this->data_table . '.stock_id', $_this->info_table . '.id' )
					->select( $_this->info_table . '.code', $_this->data_table . '.id as stock_data_id', $_this->data_table . '.data_date' )
					->where( $_this->table . '.type', $type )
					->orderBy( $_this->data_table . '.data_date' )
					->get();

		return $result;

	}

}
