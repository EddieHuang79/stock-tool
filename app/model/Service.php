<?php

namespace App\model;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class Service
{

	protected $table = "service";

	protected $user_role = 'user_role_relation';

	protected $role_service = "role_service_relation";

	protected $role = 'role';

	protected $users = 'users';

    public static function get_user_service_data()
    {	

    	$_this = new self;

		$result = DB::table($_this->user_role)
				->leftJoin($_this->role_service, $_this->user_role.'.role_id', '=', $_this->role_service.'.role_id')
				->select(
					$_this->user_role.'.user_id', 
					$_this->role_service.'.service_id'
				)
				->get();

		return $result;

    }

    public static function delete_role_service_data( $role_id )
    {	

    	$_this = new self;

		$result = DB::table($_this->role_service)->where("role_id", $role_id)->delete();

		return $result;

    }

    public static function get_list()
    {	

    	$_this = new self;

		$result = DB::table($_this->table)
				->select(
					$_this->table.'.id', 
					$_this->table.'.name',
					$_this->table.'.parents_id'
				)
				->get();

		return $result;

    }

}

