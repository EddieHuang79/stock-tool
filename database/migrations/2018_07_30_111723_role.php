<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Role extends Migration
{
    protected $user_table = 'users';
    protected $role_table = 'role';
    protected $user_role_table = 'user_role_relation';

    /**
     * Run the migrations.
     */
    public function up()
    {
        // role

        Schema::create($this->role_table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('status')->index();
            $table->timestamps();
        });

        // user_role

        Schema::create($this->user_role_table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
        });

        Schema::table($this->user_role_table, function ($table) {
            $table->foreign('user_id')->references('id')->on($this->user_table);
            $table->foreign('role_id')->references('id')->on($this->role_table);
        });

        // role

        DB::table($this->role_table)->insert(
            [
                'name' => '管理者',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );

        // user_role

        DB::table($this->user_role_table)->insert(
            [
                'user_id' => 1,
                'role_id' => 1,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists($this->user_role_table);
        Schema::dropIfExists($this->role_table);
    }
}
