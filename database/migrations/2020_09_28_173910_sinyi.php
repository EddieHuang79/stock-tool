<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Sinyi extends Migration
{
    private $table = 'sinyi';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('object_id');
            $table->integer('type'); // 1: list, 2: detail, 3: send
            $table->string('name');
            $table->string('age');
            $table->string('house_type');
            $table->string('total_area');
            $table->string('use_area');
            $table->string('spec');
            $table->string('floor');
            $table->string('has_dereactors');
            $table->string('window_in_toilet');
            $table->string('window_in_all_room');
            $table->string('link');
            $table->string('public_area')->default('');
            $table->string('management_fee')->default('');
            $table->string('park')->default('');
            $table->string('use_for')->default('');
            $table->string('notice')->default('');
            $table->dateTime('get_list_time');
            $table->dateTime('get_detail_time');
            $table->dateTime('send_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}
