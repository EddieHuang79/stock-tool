<?php

namespace App\logic;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class Record_logic
{

	// 寫log

    public function write_log( $action, $content )
    {

        $result = false;

        if ( !empty($action) && !empty($content) )
        {

            $content = is_string($content) || is_int($content) ? $content : json_encode($content);

            $path = "Syslog";

            Storage::makeDirectory($path);

            $filename = $path . "/sys_log_".date("Ymd").".txt";

            $header = "[".date("Y-m-d H:i:s")."][{$action}]$$$";

            $ori_content = Storage::exists( $filename ) ? Storage::get( $filename ) : "" ;

            $new_content = $ori_content . $header . $content . "\n" ;

            Storage::put( $filename, $new_content);

            $result = true;

        }

        return $result;

    }

	// 取log

    public function get_log( $date )
    {

    	$result = array();

    	$path = "Syslog";

        if ( !empty($date) )
        {

            $filename = $path . "/sys_log_".$date.".txt";

            $content = Storage::exists( $filename ) ? Storage::get( $filename ) : "" ;

            if (!empty($content))
            {

                $tmp = explode("\n", $content);

                foreach ($tmp as $row)
                {

                    $data = explode("$$$", $row);

                    if (!empty($data))
                    {
                        $result[] = array(
                                        "header"    => isset($data[0]) ? $data[0] : "",
                                        "content"   => isset($data[1]) ? $data[1] : ""
                                    );
                    }

                }

            }

        }

        return $result;

    }

    // 寫log

    public function write_operate_log( $action, $content )
    {

        $result = false;

        if ( !empty($action) && !empty($content) )
        {

            $content = is_string($content) || is_int($content) ? $content : json_encode($content);

            $Login_user = Session::get("Login_user");

            $account = $Login_user["account"];

            $path = "OperateLog";

            Storage::makeDirectory($path);

            $filename = $path . "/operate_log_".date("Ymd").".txt";

            $header = date("Y-m-d H:i:s")."$$$".$action."$$$".$account."$$$";

            $ori_content = Storage::exists( $filename ) ? Storage::get( $filename ) : "" ;

            $new_content = $ori_content . $header . $content . "\n" ;

            Storage::put( $filename, $new_content);

            $result = true;

        }

        return $result;

    }

    // 取log

    public function get_operate_log( $date )
    {

        $result = array();

        $path = "OperateLog";

        if ( !empty($date) )
        {

            $filename = $path . "/operate_log_{$date}.txt";

            $content = Storage::exists( $filename ) ? Storage::get( $filename ) : "" ;

            if (!empty($content))
            {

                $tmp = explode("\n", $content);

                foreach ($tmp as $row)
                {

                    $data = explode("$$$", $row);

                    if (!empty($data[0]))
                    {

                        $content = json_decode($data[3]);

                        $result[] = array(
                                        "date"      => isset($data[0]) ? $data[0] : "",
                                        "action"    => isset($data[1]) ? $data[1] : "",
                                        "account"   => isset($data[2]) ? $data[2] : "",
                                        "msg"       => isset($content->msg) ? $content->msg : "",
                                        "data"      => isset($content->data) ? implode(",", $content->data) : "",
                                    );
                    }

                }

            }

        }

        return $result;

    }

    // 寫log

    public function write_error_log( $action, $content )
    {

        $result = false;

        if ( !empty($action) && !empty($content) )
        {

            $content = is_string($content) || is_int($content) ? $content : json_encode($content);

            $Login_user = Session::get("Login_user");

            $account = $Login_user["account"];

            $path = "OperateErrorLog";

            Storage::makeDirectory($path);

            $filename = $path . "/operate_error_log_".date("Ymd").".txt";

            $header = date("Y-m-d H:i:s")."$$$".$action."$$$".$account."$$$";

            $ori_content = Storage::exists( $filename ) ? Storage::get( $filename ) : "" ;

            $new_content = $ori_content . $header . $content . "\n" ;

            Storage::put( $filename, $new_content);

            $result = true;

        }

        return $result;

    }

    // 取log

    public function get_error_log( $date )
    {

        $result = array();

        $path = "OperateErrorLog";

        if ( !empty($date) )
        {

            $filename = $path . "/operate_error_log_{$date}.txt";

            $content = Storage::exists( $filename ) ? Storage::get( $filename ) : "" ;

            if (!empty($content))
            {

                $tmp = explode("\n", $content);

                foreach ($tmp as $row)
                {

                    $data = explode("$$$", $row);

                    if (!empty($data[0]))
                    {

                        $content = json_decode($data[3]);

                        $result[] = array(
                                        "date"      => isset($data[0]) ? $data[0] : "",
                                        "action"    => isset($data[1]) ? $data[1] : "",
                                        "account"   => isset($data[2]) ? $data[2] : "",
                                        "msg"       => isset($content->msg) ? $content->msg : "",
                                        "data"      => isset($content->data) ? implode(",", $content->data) : "",
                                    );
                    }

                }

            }

        }

        return $result;

    }


    public static function getInstance()
    {

        return new self;

    }

}
