<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Role
{

	private $table = "role";

    private $user_role = 'user_role_relation';

    private $role_service = "role_service_relation";

    private $service = "service";

	public function add_role( $data )
	{

		$role_id = DB::table($this->table)->insertGetId($data);

		return $role_id;

	}

	public function add_role_service( $data )
	{

		$result = DB::table($this->role_service)->insert($data);

		return $result;

	}

	// 		管理者權限清單

	public function get_role_service_data( $role_id )
	{

		$result = DB::table($this->role_service)
				->leftJoin($this->service, $this->role_service.'.service_id', '=', $this->service.'.id')
				->select(
                    $this->role_service.'.role_id',
                    $this->role_service.'.service_id',
                    $this->service.'.parents_id',
                    $this->service.'.name as service_name'
				)
				->whereIn('role_id', $role_id)
				->get();

        return $result;

	}

	public function get_role_id_by_user_id( $user_id )
	{

		$data = DB::table($this->user_role)->select("role_id")->where("user_id", $user_id)->first();

		return $data;

	}

    public static function getInstance()
    {

        return new self;

    }

}
