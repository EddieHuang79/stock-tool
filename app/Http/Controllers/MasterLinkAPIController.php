<?php

namespace App\Http\Controllers;

use App\logic\MasterLinkAPI_logic;

class MasterLinkAPIController extends Controller
{
    
	public static function api_test()
	{
		
		MasterLinkAPI_logic::process();

	}

}
