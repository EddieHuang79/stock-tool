<?php

namespace App\logic;

abstract class Basetool
{

    // 過濾文字

    public function strFilter( $str = '' )
    {

    	$str = trim($str);

		$result = preg_replace("/[ '.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/", "", $str);

		return $result;

    }

}
