<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Role
{

	protected $table = "role";

	protected $user_role = 'user_role_relation';

	protected $role_service = "role_service_relation";

	protected $service = "service";

	public static function add_role( $data )
	{

		$_this = new self;

		$role_id = DB::table($_this->table)->insertGetId($data);

		return $role_id;

	}

	public static function add_role_service( $data )
	{

		$_this = new self;

		$result = DB::table($_this->role_service)->insert($data);

		return $result;

	}

	// 		管理者權限清單

	public static function get_role_service_data( $role_id )
	{

		$_this = new self;

		$result = DB::table($_this->role_service)
				->leftJoin($_this->service, $_this->role_service.'.service_id', '=', $_this->service.'.id')
				->select(
					$_this->role_service.'.role_id', 
					$_this->role_service.'.service_id', 
					$_this->service.'.parents_id',
					$_this->service.'.name as service_name'
				)
				->whereIn('role_id', $role_id)
				->get();

        return $result;

	}

	public static function get_role_id_by_user_id( $user_id )
	{

		$_this = new self();

		$data = DB::table($_this->user_role)->select("role_id")->where("user_id", $user_id)->first();

		return $data;

	}

}
