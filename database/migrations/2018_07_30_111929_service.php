<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Illuminate\Support\Facades\DB;

class Service extends Migration
{

    protected $role_table = "role";
    protected $service_table = "service";
    protected $role_service_table = "role_service_relation";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        // service

        Schema::create($this->service_table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('link');
            $table->integer('parents_id');
            $table->integer('status')->index();
            $table->integer('public')->default(2);
            $table->timestamps();
        });

        // role_service

        Schema::create($this->role_service_table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('role_id')->unsigned();
            $table->integer('service_id')->unsigned();
        });

        Schema::table($this->role_service_table, function($table) {
           $table->foreign('role_id')->references('id')->on('role');
           $table->foreign('service_id')->references('id')->on('service');
        });


        // service

        $service_id = [];

        $service = array(
            array(
                'name'          => '帳號管理',
                'link'          => '',
                'parents_id'    => 0,
                'status'        => 1,
                'created_at'    => date("Y-m-d H:i:s"),
                'updated_at'    => date("Y-m-d H:i:s")
            ),
            array(
                'name'          => '民宿設定',
                'link'          => '',
                'parents_id'    => 0,
                'status'        => 1,
                'created_at'    => date("Y-m-d H:i:s"),
                'updated_at'    => date("Y-m-d H:i:s")
            ),
            array(
                'name'          => '版面設定',
                'link'          => '',
                'parents_id'    => 0,
                'status'        => 1,
                'created_at'    => date("Y-m-d H:i:s"),
                'updated_at'    => date("Y-m-d H:i:s")
            ),
            array(
                'name'          => '房間/房型設定',
                'link'          => '',
                'parents_id'    => 0,
                'status'        => 1,
                'created_at'    => date("Y-m-d H:i:s"),
                'updated_at'    => date("Y-m-d H:i:s")
            ),
            array(
                'name'          => '各房型特殊設定',
                'link'          => '',
                'parents_id'    => 0,
                'status'        => 1,
                'created_at'    => date("Y-m-d H:i:s"),
                'updated_at'    => date("Y-m-d H:i:s")
            ),
            array(
                'name'          => '訂單成立通知設定',
                'link'          => '',
                'parents_id'    => 0,
                'status'        => 1,
                'created_at'    => date("Y-m-d H:i:s"),
                'updated_at'    => date("Y-m-d H:i:s")
            ),
            array(
                'name'          => '新增訂單',
                'link'          => '',
                'parents_id'    => 0,
                'status'        => 1,
                'created_at'    => date("Y-m-d H:i:s"),
                'updated_at'    => date("Y-m-d H:i:s")
            ),
            array(
                'name'          => '訂單一覽',
                'link'          => '',
                'parents_id'    => 0,
                'status'        => 1,
                'created_at'    => date("Y-m-d H:i:s"),
                'updated_at'    => date("Y-m-d H:i:s")
            ),
        );


        foreach($service as $item) 
        {

            $service_id[] = DB::table($this->service_table)->insertGetId( $item );

        }   

        $cnt = count($service);

        for ($i=1; $i <= $cnt; $i++) 
        { 

            // role_service

            DB::table($this->role_service_table)->insert(
                array(
                    'role_id'       => 1,
                    'service_id'    => $i,
                )
            );

        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
        Schema::dropIfExists($this->role_service_table);
        Schema::dropIfExists($this->service_table);

    }
}
