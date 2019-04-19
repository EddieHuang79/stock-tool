<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait stockFileLib
{

	private $parents_dir = 'stock';

	//  取得目錄下檔案清單

	private function get_dir_files()
	{

		$_this = new self();

		$data = Storage::allFiles( $_this->parents_dir );

		$result = collect( $data )->filter(function($item, $key){
			return strpos($item, ".csv");
		})->values()->toArray();

		return $result ;

	}

	// 	將檔案資料轉成轉成陣列

	private function stock_data_to_array( $fileName )
	{

		$_this = new self();

		$result = [];

		if ( !empty($fileName) && file_exists( storage_path( 'app/' . $fileName ) ) ) 
		{

			$data = Storage::get( $fileName );

			$data = explode("\r\n", $data);

			$result = collect( $data )->map(function( $item, $key ) use($_this) {
				$tmp = explode('","', $item);
				$tmp = collect( $tmp )->map(function( $item2, $key2 ){
					return str_replace('"', '', str_replace(',', '', $item2));
				})->toArray();
				return [
					"date" 		=> isset($tmp[0]) ? $_this->change_to_west_year( $tmp[0] ) : '',
					"volume" 	=> isset($tmp[1]) ? intval($tmp[1]) : '',
					"money" 	=> isset($tmp[2]) ? intval($tmp[2]) : '',
					"open" 		=> isset($tmp[3]) ? floatval($tmp[3]) : '',
					"highest" 	=> isset($tmp[4]) ? floatval($tmp[4]) : '',
					"lowest" 	=> isset($tmp[5]) ? floatval($tmp[5]) : '',
					"close" 	=> isset($tmp[6]) ? floatval($tmp[6]) : ''
				];
			})->toArray();
			
		}

		return $result;

	}


	// 		西元年轉民國年

	private function change_to_taiwan_year( $date )
	{

		return (int)substr($date, 0, 4) - 1911 . '/' . substr($date, 4, 2);

	}


	// 		民國日期轉西元日期

	private function change_to_west_year( $date )
	{

		$result = '';

		if ( !empty($date) && is_string($date) ) 
		{
	
			$tmp = explode("/", $date);
	
			$tmp[0] = 1911 + (int)$tmp[0];
	
			$result = implode("-", $tmp);

		}

		return $result;

	}


	// 		轉存股票檔案

	private function saveStockFile( $data, $date, $code, $type )
	{

		$_this = new self();

		$result = false;

		if ( !empty($data) && is_string($data) && !empty($date) && !empty($code) ) 
		{

			$file_path = $_this->parents_dir . '/' . $code;

			Storage::makeDirectory( $file_path );

			$file_name = $file_path . '/' . $date . '.csv';

			$tmp = explode("\r\n", $data);

			$cnt = count($tmp);

			$data = $type === 1 ? array_slice( $tmp, 2, $cnt - 8  ) : array_slice( $tmp, 5, $cnt - 6  ) ; 

			if ( $cnt > 0 ) 
			{

				Storage::put( $file_name , implode("\r\n", $data) );

				$result = true;
				
			}

		}

		return $result;

	}


	// 		取得已取回的股票資料檔案

	private function get_exist_data( $code )
	{

		$_this = new self();

		$result = [];

		if ( !empty($code) && is_int($code) ) 
		{

			$file_path = $_this->parents_dir . '/' . $code;		

			$files = Storage::allFiles( $file_path );

			$result = $_this->filename_to_date( $files );		
			
		}

		return $result;

	}


	// 		檔名轉日期

	private function filename_to_date( $data )
	{

		$result = [];

		if ( !empty($data) && is_array($data) ) 
		{

			$result = collect( $data )->filter(function($item, $key){
				return strpos($item, ".csv");
			})->map(function($item, $key){
				$file_name = basename($item);
				$tmp = explode(".", $file_name);
				return $tmp[0];
			})->values()->toArray();

		}

		return $result;

	}


	// 		取得空白檔案

	private function get_empty_file()
	{

		$_this = new self();

		$data = $_this->get_dir_files();

		$result = collect( $data )->filter(function($fileName, $key){
			return Storage::size( $fileName ) < 1;
		})->values()->toArray();

		return $result;

	}

}


