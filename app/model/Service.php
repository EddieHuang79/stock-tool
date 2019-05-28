<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Service
{

	private $table = "service";

    private $user_role = 'user_role_relation';

    private $role_service = "role_service_relation";

    public function get_user_service_data()
    {

		$result = DB::table($this->user_role)
				->leftJoin($this->role_service, $this->user_role.'.role_id', '=', $this->role_service.'.role_id')
				->select(
                    $this->user_role.'.user_id',
                    $this->role_service.'.service_id'
				)
				->get();

		return $result;

    }

    public function delete_role_service_data( $role_id )
    {

		$result = DB::table($this->role_service)->where("role_id", $role_id)->delete();

		return $result;

    }

    public function get_list()
    {

		$result = DB::table($this->table)
				->select(
                    $this->table.'.id',
                    $this->table.'.name',
                    $this->table.'.parents_id'
				)
				->get();

		return $result;

    }

    public static function getInstance()
    {

        return new self;

    }

}

