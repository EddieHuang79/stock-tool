<?php

namespace App\Console;

use App\jobs\CrontabCenter;
use App\logic\Holiday_logic;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        if (env('APP_DEBUG') !== false || env('APP_ENV') !== 'local') {
            return;
        }

        // 策略模擬

//        $schedule->call(function () {
//            CrontabCenter::getInstance()->bearStrategy();
//        })->cron('*/3 * * * *');

//        $schedule->call(function () {
//            CrontabCenter::getInstance()->fix_history_data(2018);
//        })->cron('*/5 2 * * *');

        // 資料切分

        // $schedule->call(function () {

        // CrontabCenter::getInstance()->divide_stock_table();
        // CrontabCenter::getInstance()->divide_tech_table();
        // CrontabCenter::getInstance()->divide_sell_buy_table();

        // })->cron("* 11,12 * * *");

        //  取得假日設定

        $is_holiday = Holiday_logic::getInstance()->is_holiday(time());

        if ($is_holiday === true) {
            return;
        }

        // 自動更新所有股票的當日資料

        $schedule->call(function () {
            CrontabCenter::getInstance()->update_daily_data();
        })->cron('* 14,15,16 * * 1-5');

        // 股票更新失敗通知

        $schedule->call(function () {
            CrontabCenter::getInstance()->update_fail_notice();
        })->cron('40 16 * * 1-5');

        //  更新失敗的檔案重新抓

        $schedule->call(function () {
            CrontabCenter::getInstance()->update_fail_daily_data();
        })->cron('45-59 16 * * 1-5');

        $schedule->call(function () {
            CrontabCenter::getInstance()->update_fail_daily_data();
        })->cron('0-10 17 * * 1-5');

        // 轉存基本股價資料

        $schedule->call(function () {
            CrontabCenter::getInstance()->auto_save_this_month_file_to_db();
        })->cron('0 15,16,17 * * 1-5');

        // 自動建立技術指標初始資料

        $schedule->call(function () {
            CrontabCenter::getInstance()->create_init_data();
        })->cron('21-25 17 * * 1-5');

        //  計算全部

        $schedule->call(function () {
            CrontabCenter::getInstance()->count_all();
        })->cron('28-37 17 * * 1-5');

        // 自動計算買賣壓力 Redis要記得清 updateDaily_{date}

        $schedule->call(function () {
            CrontabCenter::getInstance()->count_sellBuyPercent();
        })->cron('38-47 17 * * 1-5');

        // 透過Line自動回報選股條件

        $schedule->call(function () {
            CrontabCenter::getInstance()->BollingerBuy();
        })->cron('50 17 * * 1-5');

        $schedule->call(function () {
            CrontabCenter::getInstance()->BollingerSell();
        })->cron('52 17 * * 1-5');

        $schedule->call(function () {
            CrontabCenter::getInstance()->auto_get_fund_data();
            CrontabCenter::getInstance()->auto_get_fund_data2();
        })->cron('54 17 * * 1-5');

        $schedule->call(function () {
            CrontabCenter::getInstance()->save_fund_data_from_text();
        })->cron('56 17 * * 1-5');

        // 自動建立空白檔案

        $schedule->call(function () {
            CrontabCenter::getInstance()->create_empty_file();
        })->cron('* * 1 * *');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
