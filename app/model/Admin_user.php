<?php

namespace App\model;

use Illuminate\Support\Facades\DB;

class Admin_user
{

   protected $table = 'users';

   protected $user_role = 'user_role_relation';

   protected $role = 'role';

   protected $role_service = "role_service_relation";

   public static function get_list( $page_size = 30, $orderBy = 'account', $sort = 'asc' )
   {

      $_this = new self;

      $result = DB::table($_this->table)
            ->leftJoin($_this->user_role, $_this->user_role.'.user_id', '=', $_this->table.'.id')
            // ->leftJoin($_this->role_service, $_this->user_role.'.role_id', '=', $_this->role_service.'.role_id')
            ->select(
               $_this->table.'.id',
               $_this->table.'.account',
               $_this->table.'.email',
               $_this->table.'.status',
               $_this->user_role.'.role_id'
            )
            ->orderBy($orderBy, $sort)
            ->paginate($page_size);

      return $result;

   }

   public static function get_data( $id = 0 )
   {

         $_this = new self;
      
         $user = DB::table($_this->table)
                  ->leftJoin($_this->user_role, $_this->user_role.'.user_id', '=', $_this->table.'.id')
                  ->select(
                     $_this->table.'.id',
                     $_this->table.'.account',
                     $_this->table.'.ori_password',
                     $_this->table.'.email',
                     $_this->table.'.status',
                     $_this->user_role.'.role_id'
                  )
                  ->where($_this->table.".id", $id)
                  ->get();

         return $user;

   }

   public static function get_user_data()
   {

         $_this = new self;
      
         $user = DB::table($_this->table)->get();

         return $user;

   }

   public static function get_active_user()
   {

      $_this = new self();

      $data = DB::table($_this->table)->where("status", "=", "1")->get();

      return $data;

   }

   public static function add_user( $data )
   {

         $_this = new self;
      
         $user_id = DB::table($_this->table)->insertGetId($data);

         return $user_id;

   }

   public static function edit_user( $data, $where )
   {

         $_this = new self;
      
         $result = DB::table($_this->table)->where('id', $where)->update($data);

         return $result;

   }

   public static function get_user_role_by_id( $id )
   {

         $_this = new self;
      
         $user_role = DB::table($_this->user_role)
                     ->leftJoin($_this->role, 'user_role_relation.role_id', '=', 'role.id')
                     ->select('user_role_relation.id', 'user_role_relation.role_id', 'role.name')
                     ->where("user_role_relation.user_id", "=", $id)
                     ->where($_this->role.".status", "=", 1)
                     ->orderBy($_this->role.'.id')
                     ->get();

         return $user_role;

   }

   public static function get_user_role()
   {

   		$_this = new self;
   	
         $user_role = DB::table($_this->user_role)
                     ->leftJoin($_this->role, 'user_role_relation.role_id', '=', 'role.id')
                     ->select('user_role_relation.id', 'user_role_relation.user_id', 'user_role_relation.role_id', 'role.name')
                     ->where($_this->role.".status", "=", 1)
                     ->get();

   		return $user_role;

   }

   public static function add_user_role( $data )
   {

         $_this = new self;
      
         $result = DB::table($_this->user_role)->insert($data);

         return $result;

   }

   public static function delete_user_role( $user_id )
   {

         $_this = new self;
      
         $result = DB::table($_this->user_role)->where('user_id', '=', $user_id)->delete();

         return $result;

   }


   public static function get_user_id( $data )
   {

         $_this = new self;
      
         $result = DB::table($_this->table)->where('account', '=', $data["account"])->first();

         $result = !empty($result->id) ? $result->id : 0 ; 

         return $result;

   }

   public static function get_user_id_by_role( $role_id )
   {

         $_this = new self;
      
         $user = DB::table($_this->user_role)->select('user_id')->where('role_id', '=', $role_id)->groupBy('user_id')->get();

         return $user;

   }

   public static function find_user_by_assign_column( $column, $data, $id )
   {

         $_this = new self;
      
         $user = DB::table($_this->table)->select('id')->where( $column, $data );

         $user = !empty($id) ? $user->where( 'id', '!=', $id ) : $user ;

         $user = $user->get();

         return $user;

   }

   public static function get_table_schema()
   {

      $_this = new self();

      $result = DB::select("SHOW COLUMNS FROM ". $_this->table);

      return $result;

   }

   public static function disable( $id )
   {

      $_this = new self;

      $result = DB::table($_this->table)->where('id', $id)->update(array("status" => 2));

      return $result;

   }


   // 以關鍵字取得資料

   public static function get_data_by_keyword( $keyword )
   {

      $_this = new self();

      $data = DB::table($_this->table)
               ->where('account', "like", '%' . $keyword . '%')
               ->orWhere('name', "like", '%' . $keyword . '%')
               ->where("status", 1)
               ->get();

      return $data;

   }


   // 以關鍵字取得資料

   public static function user_data_mapping( $keyword )
   {

      $_this = new self();

      $data = DB::table($_this->table)
               ->where('account', $keyword)
               ->orWhere('name', $keyword)
               ->where("status", 1)
               ->get();

      return $data;

   }

   // 以關鍵字取得資料

   public static function is_mail_exist( $mail )
   {

      $_this = new self();

      $data = DB::table($_this->table)
               ->where('email', $mail)
               ->get();

      return $data;

   }

   public static function forget_and_update_password( $password, $mail )
   {

         $_this = new self;
      
         $result = DB::table($_this->table)->where('email', $mail)->update(["password" => $password, "updated_at" => date("Y-m-d H:i:s")]);

         return $result;

   }


   // 以關鍵字取得資料

   public static function get_password( $user_id )
   {

      $_this = new self();

      $data = DB::table($_this->table)
               ->select("password")
               ->where('id', $user_id)
               ->first();

      return $data;

   }

}