<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use app\logic\Redis_tool;

trait stockFileLib
{

	private $parents_dir = 'stock';

	private function get_sub_dir()
	{

		$getDir = $this->parents_dir;

		$data = Storage::directories( $getDir );

		$result = collect( $data )->map(function($item, $key){
			$tmp = explode("/", $item);
			return $tmp[1];
		})->toArray();

		return $result;

	}

	//  取得目錄下檔案清單

	private function get_dir_files( $dir = '' )
	{

		$getDir = $this->parents_dir;

		$getDir.= !empty($dir) ? '/' . $dir : '' ;

		$data = Storage::allFiles( $getDir );

		$result = collect( $data )->filter(function($item, $key){
			return strpos($item, ".csv");
		})->values()->toArray();

		return $result ;

	}

	// 	將檔案資料轉成轉成陣列

	private function stock_data_to_array( $fileName )
	{

		$result = [];

		if ( !empty($fileName) && file_exists( storage_path( 'app/' . $fileName ) ) )
		{

			$data = Storage::get( $fileName );

			$data = explode("\r\n", $data);

			$result = collect( $data )->map(function( $item, $key ) {
				$tmp = explode('","', $item);
				$tmp = collect( $tmp )->map(function( $item2, $key2 ){
					return str_replace('"', '', str_replace(',', '', $item2));
				})->toArray();
				return [
					"date" 		=> isset($tmp[0]) ? $this->change_to_west_year( $tmp[0] ) : '',
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
	// 		新增規則，如果拿到的檔案跟原本內容相同，表示股票有問題，加入排除清單，免得卡著

	private function saveStockFile( $data, $date, $code, $type )
	{

		$result = false;

		if ( !empty($data) && is_string($data) && !empty($date) && !empty($code) )
		{

			$sub = floor($code / 1000) * 1000;

			$sub = $sub > 9999 ? 9000 : $sub ;

			$file_path = $this->parents_dir . '/st' . $sub . '/' . $code;

			Storage::makeDirectory( $file_path );

			$file_name = $file_path . '/' . $date . '.csv';

			$tmp = explode("\r\n", $data);

			$cnt = count($tmp);

			$data = $type === 1 ? array_slice( $tmp, 2, $cnt - 8  ) : array_slice( $tmp, 5, $cnt - 6  ) ;

			$ori_content =  file_exists( storage_path( $file_name ) ) ? Storage::get( $file_name ) : '' ;

			$new_content = implode("\r\n", $data);

			if ( $cnt > 0 && $ori_content !== $new_content )
			{

				Storage::put( $file_name , implode("\r\n", $data) );

				$result = true;

			}

			if ( $ori_content === $new_content )
			{

                Redis_tool::getInstance()->setFilterStock( (int)$code );

			}

		}

		return $result;

	}


	// 		取得已取回的股票資料檔案

	private function get_exist_data( $code )
	{

		$result = [];

		if ( !empty($code) && is_int($code) )
		{

			$sub = floor($code / 1000) * 1000;

			$file_path = $this->parents_dir . '/st' . $sub . '/' . $code;

			$files = Storage::allFiles( $file_path );

			$result = $this->filename_to_date( $files );

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


	// 		建立空白檔案

	private function create_empty_file( $code )
	{

		$result = [];

		if ( !empty($code) && is_int($code) )
		{

			$sub = floor($code / 1000) * 1000;

			$sub = $sub < 10000 ? $sub : 9000;

			$file_path = $this->parents_dir . '/st' . $sub . '/' . $code;

			$file_name = $file_path . '/' . date("Ym01") . '.csv';

			if ( file_exists( storage_path( 'app/' . $file_name ) ) === false )
			{

				Storage::put( $file_name , '');

			}

		}

		return $result;

	}


}


