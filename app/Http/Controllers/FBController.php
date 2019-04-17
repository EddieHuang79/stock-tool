<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\logic\Record_logic;
use App\logic\FB_logic;


class FBController extends Controller
{


	// 驗證權杖用的

	public function callback(Request $request)
	{

		$token = env("FB_TOKEN");

		$mode = isset($_GET["hub_mode"]) ? $_GET["hub_mode"] : '' ;

		$challenge = isset($_GET["hub_challenge"]) ? $_GET["hub_challenge"] : '' ;

		$verify_token = isset($_GET["hub_verify_token"]) ? $_GET["hub_verify_token"] : '' ;

		Record_logic::write_operate_log('callback', $_GET);

		$result = !empty($mode) && !empty($verify_token) && $mode === 'subscribe' && $token === $verify_token ? response( $challenge , 200 )->header('Content-Type', 'text/plain') : response()->json(['error' => 'Not authorized.'], 403) ;
    
		return $result;

	}


	// 寫入訊息

	public function send_message(Request $request)
	{

		$reply_data = [
			"message"		=>	$request->message,
			"PSID"			=>	'2215901331787784',
			"msg_type"		=>	'text'
		];

		FB_logic::send_message( $reply_data );

		return response( "got it!" , 200 )->header('Content-Type', 'text/plain');

	}


}

