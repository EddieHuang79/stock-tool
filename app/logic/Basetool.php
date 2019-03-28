<?php

namespace App\logic;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;

abstract class Basetool
{

    // 過濾文字

    public function strFilter( $str = '' )
    {

    	$str = trim($str);
		
		$result = preg_replace("/[ '.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/", "", $str);

		return $result;

    }


    // 取得狀態文字陣列

    protected function get_status_array()
    {

        return [
           ["id" => 1, "name" => __('base.enable')],
           ["id" => 2, "name" => __('base.disable')]
        ];

    }

    // getVueBaseSchema

    protected function getVueBaseSchema()
    {

        return array(
            "header" => [],
            "data" => [],
            // "auth" => [],
            "insertSchema" => [],
            "listSchema" => [],
            "mappingData" => [],
            "filterSchema" => [],
            "route" => []
        );

    }

    // setSearchParam

    protected function setSearchParam( $except_for = '', $data )
    {

        unset($data["id"]);

        $searchParam = Session::get("searchParam");

        $tmp = isset($searchParam[$except_for]) ? $searchParam[$except_for] : array();

        unset($searchParam);

        $searchParam[$except_for] = $this->emptyTest( $data ) === false ? $data : $tmp ;

        Session::forget('searchParam');

        Session::put("searchParam", $searchParam);

        return $searchParam;

    }


    // empty test

    protected function emptyTest( $data )
    {

        $result = true;

        foreach ($data as $key => $row) 
        {

            if ( ( !empty($row) || is_numeric($row) ) && $key !== 'id' ) 
            {

                $result = false;

            }

        }

        return $result;

    }

    // empty test

    protected function data_type_check( $data )
    {

        $result = true;

        if ( !empty($data) && is_array($data) ) 
        {

            $result = isset($data["code"]) && !empty($data["code"]) && !is_string($data["code"]) ? false : $result;
            
            $result = isset($data["site"]) && !empty($data["site"]) && !is_string($data["site"]) ? false : $result;
            
            $result = isset($data["serial_no"]) && !empty($data["serial_no"]) && !is_string($data["serial_no"]) ? false : $result;
            
            $result = isset($data["account"]) && !empty($data["account"]) && !is_string($data["account"]) ? false : $result;
            
            $result = isset($data["test_result"]) && !empty($data["test_result"]) && !is_string($data["test_result"]) ? false : $result;
            
            $result = isset($data["test_code"]) && !empty($data["test_code"]) && !is_string($data["test_code"]) ? false : $result;

            $result = isset($data["sys_time"]) && !empty($data["sys_time"]) && ( !is_string($data["sys_time"]) || strtotime($data["sys_time"]) === false || strpos($data["sys_time"], '/') === false || strpos($data["sys_time"], ':') === false ) ? false : $result;

            $result = isset($data["actual_time"]) && !empty($data["actual_time"]) && ( !is_string($data["actual_time"]) || strtotime($data["actual_time"]) === false || strpos($data["sys_time"], '/') === false || strpos($data["sys_time"], ':') === false ) ? false : $result;
            
        }

        return $result;

    }


}