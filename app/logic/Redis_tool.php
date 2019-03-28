<?php

namespace App\logic;

use Illuminate\Support\Facades\Redis;
use App\Traits\SchemaFunc;

class Redis_tool
{

	use SchemaFunc;

	protected $access_token_key = "accessToken_";

	protected $work_time_key = "workTime_";

	protected $user_service_key = "userService_";

	protected $online_user_key = "onlineUser_";

	protected $ckfinder_key = "ckfinder_";

	protected function delete_redis_data( $data )
	{

		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{

			foreach ($data as $key) 
			{

				Redis::del( $key );

			}
			
			$result = true;

		}

		return $result;

	}


	// 設定Access_token

	public static function set_access_token( $data )
	{

		$_this = new self();

		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{

			$deadline = time() + $data["expires_in"];

			$access_token_key = $_this->access_token_key . $deadline;

			Redis::set( $access_token_key, json_encode($data) );

			$result = true;

		}

		return $result;

	}


	// 取得服務 - 工作項目對應陣列

	public static function get_access_token()
	{

		$_this = new self();

		$result = array();

		$access_token_key = $_this->access_token_key . '*';

		$match_key = Redis::KEYS( $access_token_key );

		if ( !empty($match_key) ) 
		{

			$tmp = explode("_", $match_key[0]);

			$data = $tmp[1] < time() ? '' : Redis::get( $match_key[0] ) ;
			
		}

		// 刪掉過期的key

		if ( empty($data) && !empty($match_key) ) 
		{
		
			$_this->delete_redis_data( $match_key );
			
		}

		$result = !empty($data) ? json_decode($data, true) : array() ;

		return $result;

	}


	// 刪除工時設定資料

	public static function delete_work_time_data()
	{

		$_this = new self();

		$deadline = time() + 1800;

		$work_time_key = $_this->work_time_key . '*';

		$match_key = Redis::KEYS( $work_time_key );

		$_this->delete_redis_data( $match_key );

		$result = true;

		return $result;

	}


	// 設定工時設定資料

	public static function set_work_time_data( $data )
	{

		$_this = new self();

		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{

			$deadline = time() + 1800;

			$work_time_key = $_this->work_time_key . $deadline;

			Redis::set( $work_time_key, json_encode($data) );

			$result = true;

		}

		return $result;

	}


	// 取得工時設定資料

	public static function get_work_time_data()
	{

		$_this = new self();

		$result = array();

		$work_time_key = $_this->work_time_key . '*';

		$match_key = Redis::KEYS( $work_time_key );

		if ( !empty($match_key) ) 
		{

			$tmp = explode("_", $match_key[0]);

			$data = $tmp[1] < time() ? '' : Redis::get( $match_key[0] ) ;
			
		}

		// 刪掉過期的key

		if ( empty($data) && !empty($match_key) ) 
		{

			$_this->delete_redis_data( $match_key );

		}

		$result = !empty($data) ? json_decode($data, true) : array() ;

		return $result;

	}


	// 設定權限資料

	public static function set_user_service_data( $data )
	{

		$_this = new self();

		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{

			$deadline = time() + 1800;

			$user_service_key = $_this->user_service_key . $deadline;

			Redis::set( $user_service_key, json_encode($data) );

			$result = true;

		}

		return $result;

	}


	// 取得權限資料

	public static function get_user_service_data()
	{

		$_this = new self();

		$result = array();

		$user_service_key = $_this->user_service_key . '*';

		$match_key = Redis::KEYS( $user_service_key );

		if ( !empty($match_key) ) 
		{

			$tmp = explode("_", $match_key[0]);

			$data = $tmp[1] < time() ? '' : Redis::get( $match_key[0] ) ;
			
		}

		// 刪掉過期的key

		if ( empty($data) && !empty($match_key) ) 
		{

			$_this->delete_redis_data( $match_key );

		}

		$result = !empty($data) ? json_decode($data, true) : array() ;

		return $result;

	}


	// 清除權限資料

	public static function clear_user_service_data()
	{

		$_this = new self();

		$result = true;

		$user_service_key = $_this->user_service_key . '*';

		$match_key = Redis::KEYS( $user_service_key );

		$_this->delete_redis_data( $match_key );

		return $result;

	}


	// 線上帳號
	// [id, account, login_time]

	public static function set_online_user( $data )
	{

		$_this = new self;

		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{

			$online_user_key = $_this->online_user_key;

			Redis::RPUSH( $online_user_key, json_encode($data) );

			$result = true;

		}

		return $result;

	}

	public static function get_online_user()
	{

		$_this = new self;

		$result = [];

		$account = [];

		$online_user_key = $_this->online_user_key;

		$data = Redis::LRANGE( $online_user_key, 0, -1 );

		if ( !empty($data) ) 
		{

			// 反轉一次

			$data = array_reverse($data) ;

			foreach ($data as $row) 
			{

				$tmp = json_decode($row, true);

				if ( !in_array($tmp["account"], $account) ) 
				{

					$result[] = $tmp;

					$account[] = $tmp["account"];
					
				}

			}
			
		}

		if ( !empty($result) ) 
		{

			// 刪除就資料

			Redis::del( $online_user_key );

			// 反轉回來 寫入新資料

			$result = array_reverse($result) ;

			foreach ($result as $row) 
			{

				$_this->set_online_user( $row );

			}
			
		}

		return $result;

	}

	// 刪除線上帳號

	public static function del_online_user()
	{

		$_this = new self();

		$deadline = time() + 1800;

		$online_user_key = $_this->online_user_key . '*';

		$match_key = Redis::KEYS( $online_user_key );

		$_this->delete_redis_data( $match_key );

		$result = true;

		return $result;

	}

	// 刪除線上帳號(登出時觸發)

	public static function del_assign_online_user( $value, $key = 'token' )
	{

		$_this = new self();

		$result = false;

		if ( !empty($value) && is_string($value) ) 
		{

			$online_user_key = $_this->online_user_key;

			$data = Redis::LRANGE( $online_user_key, 0, -1 );

			$result = [];

			if ( !empty($data) ) 
			{

				foreach ($data as $row) 
				{

					$tmp = json_decode($row, true);

					if ( $tmp[$key] !== $value ) 
					{

						$result[] = $tmp;
						
					}

				}

				$_this->del_online_user();

				foreach ($result as $row) 
				{

					$_this->set_online_user( $row );

				}
				
			}

			$result = true;
				
		}

		return $result;

	}


	// 	取得帳號資料

	public static function get_user( $token )
	{

		$_this = new self;

		$result = [];

		$online_user_key = $_this->online_user_key;

		$data = Redis::LRANGE( $online_user_key, 0, -1 );

		if ( !empty($data) ) 
		{

			foreach ($data as $row) 
			{

				$tmp = json_decode($row, true);

				if ( $tmp["token"] === $token ) 
				{

					$result = $tmp;

					break;
				}

			}
			
		}

		return $result;		

	}


	// // 設定ckfinder路徑
	// // [id, account, login_time]

	// public static function set_ckfinder_image_dir( $custom_form_id, $file_path )
	// {

	// 	$_this = new self;

	// 	$result = false;

	// 	if ( !empty($custom_form_id) && is_int($custom_form_id) && !empty($file_path) && is_string($file_path) ) 
	// 	{

	// 		$ckfinder_key = $_this->ckfinder_key . $custom_form_id;

	// 		Redis::RPUSH( $ckfinder_key, $file_path );

	// 		$result = true;

	// 	}

	// 	return $result;

	// }

	// public static function get_ckfinder_image_dir( $custom_form_id )
	// {

	// 	$_this = new self;

	// 	$result = [];

	// 	if ( !empty($custom_form_id) && is_int($custom_form_id) ) 
	// 	{

	// 		$ckfinder_key = $_this->ckfinder_key . $custom_form_id;

	// 		$result = Redis::LRANGE( $ckfinder_key, 0, -1 );
			
	// 	}

	// 	return $result;

	// }

	// // 刪除線上帳號

	// public static function clear_ckfinder_image_dir( $custom_form_id )
	// {

	// 	$_this = new self();

	// 	$result = false;

	// 	if ( !empty($custom_form_id) && is_int($custom_form_id) ) 
	// 	{

	// 		$ckfinder_key = $_this->ckfinder_key . $custom_form_id;

	// 		$_this->delete_redis_data( [ $ckfinder_key ] );
			
	// 		$result = true;

	// 	}

	// 	return $result;

	// }


}


