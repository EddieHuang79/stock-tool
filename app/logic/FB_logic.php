<?php

namespace App\logic;

use App\Traits\SchemaFunc;
use Ixudra\Curl\Facades\Curl;

class FB_logic
{

	use SchemaFunc;

	// send message

	public function send_message( $data )
	{

		$result = false;

		if ( !empty($data) && is_array($data) )
		{

			// send to FB

			$getEventsUrl = env('FB_URL') . env('FB_TOKEN');

			$queryParams = array(
				'messaging_type'          => "RESPONSE",
				'recipient'           	  => ["id" 		=> $data["PSID"]],
				'message'           	  => ["text" 	=> $data["message"], "metadata" => 'send by my api' . date("Ymd His")]
			);

			Record_logic::getInstance()->write_operate_log( 'send_message INPUT', $getEventsUrl );

			$api_result = Curl::to( $getEventsUrl )
			->withContentType('application/json')
			->withData( $queryParams )
			->asJson()
			->post();

			Record_logic::getInstance()->write_operate_log( 'send_message OUTPUT', $api_result );

			$result = true;

		}

		return $result;

	}

    public static function getInstance()
    {

        return new self;

    }


}
