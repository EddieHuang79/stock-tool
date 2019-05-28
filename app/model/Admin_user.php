<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Admin_user
{

    private $table = 'users';

    private $user_role = 'user_role_relation';

    private $role = 'role';

    public function get_list( $page_size = 30, $orderBy = 'account', $sort = 'asc' )
    {

        $result = DB::table($this->table)
            ->leftJoin($this->user_role, $this->user_role.'.user_id', '=', $this->table.'.id')
            ->select(
                $this->table.'.id',
                $this->table.'.account',
                $this->table.'.email',
                $this->table.'.status',
                $this->user_role.'.role_id'
            )
            ->orderBy($orderBy, $sort)
            ->paginate($page_size);

        return $result;

    }

    public function get_data( $id = 0 )
    {

        $user = DB::table($this->table)
            ->leftJoin($this->user_role, $this->user_role.'.user_id', '=', $this->table.'.id')
            ->select(
                $this->table.'.id',
                $this->table.'.account',
                $this->table.'.ori_password',
                $this->table.'.email',
                $this->table.'.status',
                $this->user_role.'.role_id'
            )
            ->where($this->table.".id", $id)
            ->get();

        return $user;

    }

    public function get_user_data()
    {

        $user = DB::table($this->table)->get();

        return $user;

    }

    public function get_active_user()
    {

        $data = DB::table($this->table)->where("status", "=", "1")->get();

        return $data;

    }

    public function add_user( $data )
    {

        $user_id = DB::table($this->table)->insertGetId($data);

        return $user_id;

    }

    public function edit_user( $data, $where )
    {

        $result = DB::table($this->table)->where('id', $where)->update($data);

        return $result;

    }

    public function get_user_role_by_id( $id )
    {

        $user_role = DB::table($this->user_role)
            ->leftJoin($this->role, 'user_role_relation.role_id', '=', 'role.id')
            ->select('user_role_relation.id', 'user_role_relation.role_id', 'role.name')
            ->where("user_role_relation.user_id", "=", $id)
            ->where($this->role.".status", "=", 1)
            ->orderBy($this->role.'.id')
            ->get();

        return $user_role;

    }

    public function get_user_role()
    {

        $user_role = DB::table($this->user_role)
            ->leftJoin($this->role, 'user_role_relation.role_id', '=', 'role.id')
            ->select('user_role_relation.id', 'user_role_relation.user_id', 'user_role_relation.role_id', 'role.name')
            ->where($this->role.".status", "=", 1)
            ->get();

        return $user_role;

    }

    public function add_user_role( $data )
    {

        $result = DB::table($this->user_role)->insert($data);

        return $result;

    }

    public function delete_user_role( $user_id )
    {

        $result = DB::table($this->user_role)->where('user_id', '=', $user_id)->delete();

        return $result;

    }


    public function get_user_id( $data )
    {

        $result = DB::table($this->table)->where('account', '=', $data["account"])->first();

        $result = !empty($result->id) ? $result->id : 0 ;

        return $result;

    }

    public function get_user_id_by_role( $role_id )
    {

        $user = DB::table($this->user_role)->select('user_id')->where('role_id', '=', $role_id)->groupBy('user_id')->get();

        return $user;

    }

    public function find_user_by_assign_column( $column, $data, $id )
    {

        $user = DB::table($this->table)->select('id')->where( $column, $data );

        $user = !empty($id) ? $user->where( 'id', '!=', $id ) : $user ;

        $user = $user->get();

        return $user;

    }

    public function get_table_schema()
    {

        $result = DB::select("SHOW COLUMNS FROM ". $this->table);

        return $result;

    }

    public function disable( $id )
    {

        $result = DB::table($this->table)->where('id', $id)->update(array("status" => 2));

        return $result;

    }


    // 以關鍵字取得資料

    public function get_data_by_keyword( $keyword )
    {

        $data = DB::table($this->table)
            ->where('account', "like", '%' . $keyword . '%')
            ->orWhere('name', "like", '%' . $keyword . '%')
            ->where("status", 1)
            ->get();

        return $data;

    }


    // 以關鍵字取得資料

    public function user_data_mapping( $keyword )
    {

        $data = DB::table($this->table)
            ->where('account', $keyword)
            ->orWhere('name', $keyword)
            ->where("status", 1)
            ->get();

        return $data;

    }

    // 以關鍵字取得資料

    public function is_mail_exist( $mail )
    {

        $data = DB::table($this->table)
            ->where('email', $mail)
            ->get();

        return $data;

    }

    public function forget_and_update_password( $password, $mail )
    {

        $result = DB::table($this->table)->where('email', $mail)->update(["password" => $password, "updated_at" => date("Y-m-d H:i:s")]);

        return $result;

    }


    // 以關鍵字取得資料

    public function get_password( $user_id )
    {

        $data = DB::table($this->table)
            ->select("password")
            ->where('id', $user_id)
            ->first();

        return $data;

    }

    public static function getInstance()
    {

        return new self;

    }


}
