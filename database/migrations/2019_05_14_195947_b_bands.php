<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BBands extends Migration
{
    protected $table = 'technical_analysis';

    /**
     * Run the migrations.
     */
    public function up()
    {
        /*
            壓力線
            支撐線
            %b指標
            帶寬指標
        */

        Schema::table($this->table, function (Blueprint $table) {
            $table->float('MA20')->after('OSC')->default(0.0);
            $table->float('upperBand')->after('MA20')->default(0.0);
            $table->float('lowerBand')->after('upperBand')->default(0.0);
            $table->float('percentB')->after('lowerBand')->default(0.0);
            $table->float('bandwidth')->after('percentB')->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn('MA20');
            $table->dropColumn('upperBand');
            $table->dropColumn('lowerBand');
            $table->dropColumn('percentB');
            $table->dropColumn('bandwidth');
        });
    }
}
