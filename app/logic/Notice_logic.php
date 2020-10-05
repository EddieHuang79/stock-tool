<?php

namespace App\logic;

class Notice_logic
{
    public function noticeUser($type, $msg, $onlyAdmin = false)
    {
        if (!empty($type) && \is_int($type) && \strlen($msg) > 0) {
            switch ($type) {
                // FB

                case 1:

                    $reply_data = [
                        'message' => $msg,
                        'PSID' => '2363717870345037',
                        'msg_type' => 'text',
                    ];

                    FB_logic::getInstance()->send_message($reply_data);

                    break;
                // Line

                case 2:

                    $Line = Line_logic::getInstance();

                    $user_id = $Line->get_user_id_list();

                    $user_id = $onlyAdmin === true ? ['U1f4fa85618159c967669af63259916ba'] : ['U1f4fa85618159c967669af63259916ba', 'U1a19e56ced37a863a4a489749dad71a8'];

                    $Line->multicast_message($user_id, $msg);

                    break;
            }
        }

        return true;
    }
}
