<?php

namespace App\logic;

class Notice_logic
{

	public function noticeUser( $type, $msg )
	{

		if ( !empty($type) && is_int($type) && strlen($msg) > 0 ) 
		{

			switch ($type) 
			{

				// FB

				case 1:

					$reply_data = [
						"message"		=>	$msg,
						"PSID"			=>	'2363717870345037',
						"msg_type"		=>	'text'
					];

					FB_logic::send_message( $reply_data );

					break;
				
				// Line

				case 2:

					$user_id = Line_logic::get_user_id_list();

					Line_logic::multicast_message( $user_id, $msg );

					break;

			}
			
		}

		return true;

	}

}
