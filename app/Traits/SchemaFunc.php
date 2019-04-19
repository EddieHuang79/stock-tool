<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;
use App\logic\Record_logic;
use Illuminate\Pagination\LengthAwarePaginator;

trait SchemaFunc
{

	protected $fileLimitSize = 3.5 * 1024 * 1024;

	// 頁數處理

	protected function get_page()
	{

		$result = isset($_GET["page"]) ? (int)$_GET["page"] : 0;

		Session::put("page", $result);

		return $result;

	}


	// 取得搜尋變數

	protected function get_search_query()
	{

		$tmp = Session::get("searchParam");

		$tmp[$this->key] = isset($tmp[$this->key]) ? $tmp[$this->key] : array();

		$result = $tmp[$this->key];

		return $result;

	}


	// 取得麵包屑資料

	protected function get_path_info()
	{

		return json_encode( array( $this->action ) );

	}


	// 取得成功訊息

	protected function get_success_msg()
	{

		$result = Session::get( 'SuccessMsg' );

		Session::forget('SuccessMsg');

		return $result;

	}


	// 取得錯誤訊息

	protected function get_error_msg()
	{

		$result = Session::get( 'ErrorMsg' );

		Session::forget('ErrorMsg');

		return $result;

	}


	// 取得原始輸入資料

	protected function get_ori_data()
	{

		$result = Session::get( 'OriData' );

		Session::forget('OriData');

		return $result;

	}


	// 將schema設為只能讀取

	protected function readOnly( $data, $attr )
	{

		$result = '';

		if ( !empty($data) && !empty($attr) ) 
		{

			$tmp1 = json_decode($data, true);

			$tmp2 = json_decode($tmp1[$attr], true);

			foreach ($tmp2 as &$row) 
			{

				if ( in_array($row["type"], array(1, 4, 7, 12)) ) 
				{

					$row["type"] = 5;

				}

			}

			$tmp1[$attr] = json_encode($tmp2);

			$result = json_encode($tmp1);

		}

		return $result;

	}


	// 取得成功訊息

	protected function set_success_msg( $type = 1 )
	{

		$position = $type === 1 ? "新增" : "修改";

		$msg = $type === 1 ? $this->txt["create_success"] : $this->txt["edit_success"];

		$action = $this->action["name"] . "-" . $position;

		$content = json_encode($_POST);

		Record_logic::write_operate_log( $action, $content );

		Session::put( 'SuccessMsg', $msg );

	}


	// 取得錯誤訊息

	protected function set_error_msg( $e, $position )
	{

		$error_result = $e->getMessage();

		Session::put( 'ErrorMsg', $error_result );

		$action = $position;

		$content = json_encode($error_result);

		Record_logic::write_error_log( $action, $content );

	}


	// 整數轉時間

	protected function time_to_string( $number )
	{

		$result = '';

		if ( !empty($number) && is_int($number) ) 
		{

			$hour = (int)floor( $number / ( 60 * 60 ) ) ;

			$number = $hour > 0 ? $number - $hour * 60 * 60 : $number ;

			$minute = (int)floor( $number / 60 ) ;

			$number = $minute > 0 ? $number - $minute * 60 : $number ;

			$second = $number;

			$time = array(
				"hour" 	=> str_pad( $hour, 2, "0", STR_PAD_LEFT),
				"min"	=> str_pad( $minute, 2, "0", STR_PAD_LEFT),
				"sec"	=> str_pad( $second, 2, "0", STR_PAD_LEFT)
			);

			$result = implode(":", $time);
			
		}

		return $result;

	}


	// 		頁籤資料
	// 		@	https://arjunphp.com/laravel-5-pagination-array/

	protected function set_array_page( $data )
	{

		$result = array();

		if ( !empty($data) && is_array($data) ) 
		{

			// Get current page form url e.x. &page=1

			$currentPage = LengthAwarePaginator::resolveCurrentPage();

			// Create a new Laravel collection from the array data

			$itemCollection = collect( $data );

			// Define how many items we want to be visible in each page

			$perPage = 1;

			// Slice the collection to get the items to display in current page

			$currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

			// Create our paginator and pass it to the view

			$result = new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

			// set url path for generted links

			$result->setPath( url()->full() );
			
		}

		return $result;

	}


	// 		在陣列設定啟用文字

	protected function set_status_txt( $data )
	{

		$_this = new self();

		$result = array();

		if ( !empty($data) && is_array($data) ) 
		{

			$status_txt = [
				["id" => 1, "name" => __('base.enable')],
				["id" => 2, "name" => __('base.disable')]
			];
			
			$status_array = $_this->map_with_key( $status_txt, $key1 = "id", $key2 = "name" );

			$collection = collect( $data );

			$result = $collection->map(function ($item, $key) use( $status_array ) {

				$item->status = [
					"id" 	=> $item->status,
					"name" 	=> isset($status_array[$item->status]) ? $status_array[$item->status] : ''
				];

				return $item;

			})
			->all();

		}

		return $result;

	}


	// 		設定表頭文字

	protected function set_header_txt( $data, $txt )
	{

		$_this = new self();

		$result = array();

		if ( !empty($data) && is_array($data) && !empty($txt) && is_array($txt) ) 
		{

			$result = collect( $data )->pluck( "Field" )->filter(function ($value, $key) use( $txt ) {
			    return !empty($txt[$value]);
			})->reduce(function ($result, $item) use( $txt ) {
				$result = isset($result) ? $result : [] ;
				return array_merge( $result, [$item => $txt[$item]] );
			});

		}

		return $result;

	}


	// 		unique array handle

	protected function get_unique_array( $data, $key )
	{

		return collect( $data )
			->pluck( $key )
			->unique()
			->values()
			->toArray();

	}


	// 		map array handle

	protected function pluck( $data, $key )
	{

		return collect( $data )
			->pluck( $key )
			->toArray();

	}


	// 		map group

	protected function map_to_groups( $data, $key1, $key2 )
	{

		return collect( $data )
			->mapToGroups(function ($item, $key) use( $key1, $key2 ) {
				$item = get_object_vars($item);
				return [$item[$key1] => $item[$key2]];
			})
			->toArray();

	}


	// 		map with key

	protected function map_with_key( $data, $key1, $key2 )
	{

		return collect( $data )
			->mapWithKeys(function ($item, $key) use( $key1, $key2 ) {
				$item = is_object($item) ? get_object_vars($item) : $item ;
				return [$item[$key1] => $item[$key2]];
			})
			->toArray();

	}


	// 		map with key assign default value

	protected function map_with_key_assign_default_value( $data, $key1, $default )
	{

		return collect( $data )
			->mapWithKeys(function ($item, $key) use( $key1, $default ) {
				$item = is_object($item) ? get_object_vars($item) : $item ;
				return [$item[$key1] => $default];
			})
			->toArray();

	}


	// 		only return assign attribute

	protected function values( $data )
	{

		return collect( $data )
			->values()
			->toArray();

	}


	// 		simple set default value

	protected function set_default_value( $data, $default_value )
	{

		return collect( $data )
			->mapWithKeys(function ($item, $key) use( $default_value ) {
				return [$key => $default_value];
			})
			->toArray();

	}


	// 統一日期格式

	protected function get_string_date( $time )
	{

		$time = is_string($time) === true ? strtotime($time) : $time ; 

		$correct_time = $time > 0 ;

		return $correct_time === true ? date("Y-m-d", $time) : '' ;

	}


	// 統一日期時間格式

	protected function get_string_datetime( $time )
	{

		$time = is_string($time) === true ? strtotime($time) : $time ; 

		$correct_time = $time > 0 ;

		return $correct_time === true ? date("Y/m/d H:i", $time) : '' ;

	}


	// 取得現在日期時間

	protected function get_now_datetime()
	{

		return date("Y-m-d H:i:s");

	}


	// 		取得page data

	protected function get_pagination_data( $data )
	{

		$result = [];

		if ( $data->isNotEmpty() ) 
		{

			$result = [
				"count"  		=> $data->count(),
				"currentPage"  	=> $data->currentPage(),
				"firstItem"  	=> $data->firstItem(),
				"hasMorePages"  => $data->hasMorePages(),
				"lastItem"  	=> $data->lastItem(),
				"lastPage"  	=> $data->lastPage(),
				"nextPageUrl"  	=> $data->nextPageUrl(),
				"onFirstPage"  	=> $data->onFirstPage(),
				"perPage"  		=> $data->perPage(),
				"previousPageUrl"  => $data->previousPageUrl(),
				"total"  		=> $data->total()
			];
			
		}

		return $result;

	}


	// 		password rule
	// 		至少一碼英文+數字
	// 		介於6-8碼

	protected function pwd_rule( $pwd )
	{

		$result = false;

		if ( !empty($pwd) && is_string($pwd) ) 
		{
			
			$result = preg_match("/[^A-Za-z\s]/i", $pwd) > 0 && 
						preg_match('/[^0-9\s]/i', $pwd) > 0 &&
						strlen($pwd) >= 6 ? true : false ;
			
		}

		return $result;

	}


	// 將mime type轉成fb file type

	// 附件類型，可為 image、audio、video、file 或 template。資產的檔案大小最大可為 25 MB。

	protected function mimeType_to_file_type( $type )
	{

		$result = '';

		if ( !empty($type) && is_string($type) ) 
		{

			switch ($type) 
			{
				
				case 'image/png':
				case 'image/jpeg':
				case 'image/gif':
				case 'image/bmp':
					
					$result = 'image';

					break;
				
				case 'audio/mpeg':
				case 'audio/x-aiff':
				case 'audio/basic':
				case 'audio/midi':
				case 'audio/x-mpegurl':
				case 'audio/mp4a-latm':
				case 'audio/x-pn-realaudio':
				case 'audio/x-wav':
				case 'audio/ogg':
					
					$result = 'audio';

					break;

				case 'video/mp4':
				case 'video/flv':
				case 'video/x-msvideo':
				case 'video/x-ms-wmv':
				case 'video/mpeg':
				case 'video/quicktime':
				case 'video/x-dv':
				case 'video/vnd.mpegurl':
				case 'video/x-m4v':
				case 'video/x-sgi-movie':
				case 'video/ogg':
					
					$result = 'video';

					break;

				default:

					$result = 'file';

					break;

			}
			
		}

		return $result;

	}


	// MIME TYPE = inode/x-empty >> 錯誤的狀況，改以副檔名判別

	protected function fix_mimeType_to_file_type( $file_name )
	{

		$result = '';

		if ( !empty($file_name) && is_string($file_name) ) 
		{

			$tmp = explode(".", $file_name);

			$type = $tmp[count($tmp) - 1] ;

			switch ($type) 
			{
				
				case 'png':
				case 'jpeg':
				case 'jpg':
				case 'gif':
				case 'bmp':
					
					$result = 'image';

					break;
				
				case "3gp":
				case "aa":
				case "aac":
				case "aax":
				case "act":
				case "aiff":
				case "amr":
				case "ape":
				case "au":
				case "awb":
				case "dct":
				case "dss":
				case "dvf":
				case "flac":
				case "gsm":
				case "iklax":
				case "ivs":
				case "m4a":
				case "m4b":
				case "m4p":
				case "mmf":
				case "mp3":
				case "mpc":
				case "msv":
				case "nsf":
				case "ogg": 
				case "oga": 
				case "mogg":
				case "opus":
				case "ra":
				case "rm":
				case "raw":
				case "sln":
				case "tta":
				case "vox":
				case "wav":
				case "wma":
				case "wv":
				case "webm":
				case "8svx":
					
					$result = 'audio';

					break;

				case "webm":
				case "mkv":
				case "flv":
				case "vob":
				case "ogv":
				case "ogg":
				case "drc":
				case "gifv":
				case "mng":
				case "avi":
				case "MTS":
				case "M2TS":
				case "mov":
				case "qt":
				case "wmv":
				case "yuv":
				case "rm":
				case "rmvb":
				case "asf":
				case "amv":
				case "mp4":
				case "m4p":
				case "mp2":
				case "mpeg":
				case "mpe":
				case "mpv":
				case "mpg":
				case "m2v":
				case "m4v":
				case "svi":
				case "3gp":
				case "3g2":
				case "mxf":
				case "roq":
				case "nsv":
				case "f4v":
				case "f4p":
				case "f4a":
				case "f4b":
					
					$result = 'video';

					break;

				default:

					$result = 'file';

					break;

			}
			
		}

		return $result;

	}


	// MIME TYPE = inode/x-empty >> 錯誤的狀況，改以副檔名判別

	protected function fix_mimeType( $file_name )
	{

		$result = '';

		if ( !empty($file_name) && is_string($file_name) ) 
		{

	        $tmp_before = explode("?", $file_name);
       
			$tmp = explode(".", $tmp_before[0]);

			$type = $tmp[count($tmp) - 1] ;

			switch ($type) 
			{
				
				case 'png':
				case 'jpeg':
				case 'jpg':
				case 'gif':
				case 'bmp':
					
					// 我猜jpg會有問題

					$result = 'image' . '/' . $type ;

					break;
				
				case "3gp":
				case "aa":
				case "aac":
				case "aax":
				case "act":
				case "aiff":
				case "amr":
				case "ape":
				case "au":
				case "awb":
				case "dct":
				case "dss":
				case "dvf":
				case "flac":
				case "gsm":
				case "iklax":
				case "ivs":
				case "m4a":
				case "m4b":
				case "m4p":
				case "mmf":
				case "mp3":
				case "mpc":
				case "msv":
				case "nsf":
				case "ogg": 
				case "oga": 
				case "mogg":
				case "opus":
				case "ra":
				case "rm":
				case "raw":
				case "sln":
				case "tta":
				case "vox":
				case "wav":
				case "wma":
				case "wv":
				case "webm":
				case "8svx":
					
					$result = 'audio' . '/' . $type ;

					break;

				case "webm":
				case "mkv":
				case "flv":
				case "vob":
				case "ogv":
				case "ogg":
				case "drc":
				case "gifv":
				case "mng":
				case "avi":
				case "MTS":
				case "M2TS":
				case "mov":
				case "qt":
				case "wmv":
				case "yuv":
				case "rm":
				case "rmvb":
				case "asf":
				case "amv":
				case "mp4":
				case "m4p":
				case "mp2":
				case "mpeg":
				case "mpe":
				case "mpv":
				case "mpg":
				case "m2v":
				case "m4v":
				case "svi":
				case "3gp":
				case "3g2":
				case "mxf":
				case "roq":
				case "nsv":
				case "f4v":
				case "f4p":
				case "f4a":
				case "f4b":
					
					$result = 'video' . '/' . $type ;

					break;

				default:

					$result = 'file' . '/' . $type ;

					break;

			}
			
		}

		return $result;

	}


	// 訊息加密

	protected function encrypt_content( $content )
	{

		return encrypt( $content );

	}


	// 訊息解密

	protected function decrypt_content( $content )
	{

		return decrypt( $content );

	}	


	// 西元年轉民國年

	protected function year_change( $date )
	{

		return (int)substr($date, 0, 4) - 1911 . '/' . substr($date, 4, 2);

	}


	// 		民國日期轉西元日期

	protected function date_transformat( $date )
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

}


