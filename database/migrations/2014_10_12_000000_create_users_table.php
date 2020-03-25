<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    protected $user_table = 'users';

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create($this->user_table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('account')->unique();
            $table->string('ori_password');
            $table->string('password');
            $table->integer('status')->default(1);
            $table->string('email');
            $table->rememberToken();
            $table->timestamps();
        });

        DB::table($this->user_table)->insert(
            [
                'account' => 'admin',
                'ori_password' => '123456',
                'password' => Hash::make('123456'),
                'email' => 'admin@base.com',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists($this->user_table);
    }
}
