<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class Holiday extends Migration
{

    protected $table = 'holiday';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->date('holiday_date');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::dropIfExists( $this->table );

    }
}
