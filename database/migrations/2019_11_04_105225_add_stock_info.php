<?php

use App\model\Stock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AddStockInfo extends Migration
{
    protected $table = 'stock_info';

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->boolean('hasFutures')->after('name')->default(false);
            $table->boolean('hasOptions')->after('hasFutures')->default(false);
            $table->integer('capital')->after('hasOptions')->default(0);
            $table->integer('circulation')->after('capital')->default(0);
            $table->integer('marketValue')->after('Circulation')->default(0);
            $table->string('category')->after('marketValue')->default('');
            $table->string('ceo')->after('category')->default('');
            $table->string('manager')->after('ceo')->default('');
        });

        $stockInfo = Stock::getInstance()->get_stock_list()->mapWithKeys(function ($item) {
            return [$item->code => $item->id];
        })->toArray();

        $file = Storage::get('stock/StockInfo.csv');

        $data = explode("\r\n", $file);

        unset($data[0]);

        $data = collect($data)->filter(function ($item) use ($stockInfo) {
            $content = explode(',', $item);
            $code = (int) $content[0];

            return isset($stockInfo[$code]);
        })->mapWithKeys(function ($item) use ($stockInfo) {
            $content = explode(',', $item);
            $code = (int) $content[0];
            $stockId = $stockInfo[$code];

            return [
                $stockId => [
                    'hasFutures' => $content[2] === '有',
                    'hasOptions' => $content[3] === '有',
                    'capital' => $content[4],
                    'circulation' => $content[5],
                    'marketValue' => $content[6],
                    'category' => $content[9],
                    'ceo' => $content[10],
                    'manager' => $content[11],
                ],
            ];
        })->map(function ($data, $stockId) {
            DB::table('stock_info')->where('id', $stockId)->update($data);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn('hasFutures');
            $table->dropColumn('hasOptions');
            $table->dropColumn('capital');
            $table->dropColumn('circulation');
            $table->dropColumn('marketValue');
            $table->dropColumn('category');
            $table->dropColumn('ceo');
            $table->dropColumn('manager');
        });
    }
}
