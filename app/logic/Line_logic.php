<?php

namespace App\logic;

use Ixudra\Curl\Facades\Curl;
use App\model\Line;

class Line_logic
{


	// receive message

	/*

		array:2 [▼
		  "events" => array:1 [▼
		    0 => array:5 [▼
		      "type" => "message"
		      "replyToken" => "e7e6837063234d93ad0add936d330fe6"
		      "source" => array:2 [▼
		        "userId" => "U1f4fa85618159c967669af63259916ba"
		        "type" => "user"
		      ]
		      "timestamp" => 1555662669423
		      "message" => array:3 [▼
		        "type" => "text"
		        "id" => "9719715626603"
		        "text" => "1"
		      ]
		    ]
		  ]
		  "destination" => "U1774cef2161f5987f43a81ca15ea7bce" // 機器人的id
		]

	*/

	public static function receive_message( $data = '' )
	{

		$_this = new self();

		$data = json_decode($data, true);

		$message = $data["events"][0]["message"]["text"];

		$userId = $data["events"][0]["source"]["userId"];

		$is_exist = $_this->is_exist( $userId );

		Record_logic::write_operate_log( $action = "is_exist", $content = $is_exist );

		if ( $is_exist === false ) 
		{

			$reply_message = $_this->messageCheck( $message );

			if ( $reply_message["status"] === true ) 
			{

				$insert_format = [
					"user_id" 		=> $userId,
					"created_at" 	=> date("Y-m-d H:i:s"),
					"updated_at" 	=> date("Y-m-d H:i:s"),
				];

				$_this->add_data( $insert_format );
				
			}

			$_this->push_message( $userId, $reply_message["msg"] );
			
		}
		else
		{

			$_this->push_message( $userId, $msg = '您的資料已經在清單摟，目前本機器人功能不多，其他訊息無法有效處理，請多包涵！' );

		}

		return true;

	}


	// push message

	public static function push_message( $user_id, $message )
	{

		$_this = new self();

		$result = false;

		if ( !empty($user_id) && is_string($user_id) && !empty($message) && is_string($message) ) 
		{

			$getEventsUrl = 'https://api.line.me/v2/bot/message/push';

			$queryParams = array(
				'to'          				=> $user_id,
				'messages'           	  	=> [ ["type" => 'text', "text" => $message] ]
			);

			Record_logic::write_operate_log( 'Line Send Message INPUT', $getEventsUrl );

			$api_result = Curl::to( $getEventsUrl )
			->withHeader('Authorization: Bearer ' . env("Line_TOKEN"))
			->withData( $queryParams )
			->asJson()
			->post();

			Record_logic::write_operate_log( 'Line Send Message OUTPUT', $api_result );

			$result = true;

		}

		return $result;

	}


	// push multicast message

	public static function multicast_message( $user_id, $message )
	{

		$_this = new self();

		$result = false;

		if ( !empty($user_id) && is_array($user_id) && !empty($message) && is_string($message) ) 
		{

			$getEventsUrl = 'https://api.line.me/v2/bot/message/multicast';

			$queryParams = array(
				'to'          				=> $user_id,
				'messages'           	  	=> [ ["type" => 'text', "text" => $message] ]
			);

			Record_logic::write_operate_log( 'Line Send multicast Message INPUT', $getEventsUrl );

			$api_result = Curl::to( $getEventsUrl )
			->withHeader('Authorization: Bearer ' . env("Line_TOKEN"))
			->withData( $queryParams )
			->asJson()
			->post();

			Record_logic::write_operate_log( 'Line Send multicast Message OUTPUT', $api_result );

			$result = true;

		}

		return $result;

	}


	// reply message

	public static function reply_message( $data )
	{

		$_this = new self();

		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{

			$getEventsUrl = 'https://api.line.me/v2/bot/message/reply';

			$queryParams = array(
				// 'to'          				=> "U1f4fa85618159c967669af63259916ba",
				"replyToken"				=> "e7e6837063234d93ad0add936d330fe6",
				'messages'           	  	=> [ ["type" => 'text', "text" => $data["message"]] ]
			);

			Record_logic::write_operate_log( 'Line Reply Message INPUT', $getEventsUrl );

			$api_result = Curl::to( $getEventsUrl )
			->withHeader('Authorization: Bearer ' . env("Line_TOKEN"))
			->withData( $queryParams )
			->asJson()
			->post();

			Record_logic::write_operate_log( 'Line Reply Message OUTPUT', $api_result );

			$result = true;

		}

		return $result;

	}


	// 通關密語確認

	public static function messageCheck( $msg )
	{

		$result = [
			"status" 	=> false,
			"msg" 		=> ""
		];

		if ( !empty($msg) ) 
		{

			switch ($msg) 
			{

				case '有痘痘有斑點睡前一顆敏甘寧':
					
					$result = [
						"status" 	=> true,
						"msg" 		=> "非常好，您的帳號已加入訊息通知清單，每日的17:30左右系統會發通知喔！再請留意！"
					];

					break;
				
				default:
					
					$result["msg"] = '您還真是調皮，故意不回答正確的密語呢，再給你一次機會喔！';

					break;
			}

		}

		return $result;

	}


	// 判斷帳號是否存在

	private function is_exist( $userId )
	{

		$_this = new self();

		$result = false;

		if ( !empty($userId) && is_string($userId) ) 
		{

			$data = $_this->get_data();

			$result = collect( $data )->pluck( "user_id" )->filter( function( $item, $key ) use($userId) {
				return $userId === $item;
			} )->isNotEmpty();
			
		}

		return $result;

	}


	// 取得LineUserId資料

	private function get_data()
	{
		
		return Line::get_data();

	}


	// 寫入LineUserId

	private function add_data( $data )
	{
		
		$result = false;

		if ( !empty($data) && is_array($data) ) 
		{
			
			Line::add_data( $data );

			$result = true;
			
		}

		return $result;

	}


	// 取得LineUserId清單

	public static function get_user_id_list()
	{

		$_this = new self();

		$data = $_this->get_data();

		$result = collect( $data )->pluck( "user_id" )->toArray();
		
		return $result;

	}


}





