<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\logic\Holiday_logic;
use App\jobs\CrontabCenter;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        if ( env("APP_DEBUG") !== false || env("APP_ENV") !== 'local' ) {
            return;
        }

//        // 策略模擬
//
//        $schedule->call(function () {
//
//            CrontabCenter::getInstance()->simulation();
//
//        })->cron("* 10,11,12,13,14,15,16 * * *");

        //  取得假日設定

       $is_holiday = Holiday_logic::getInstance()->is_holiday( time() );

       if ( $is_holiday === true ) {
           return;
       }

        // 自動更新所有股票的當日資料

        $schedule->call(function () {

            CrontabCenter::getInstance()->update_daily_data();

        })->cron("* 14,15,16 * * *");

        // 股票更新失敗通知

        $schedule->call(function () {

            CrontabCenter::getInstance()->update_fail_notice();

        })->cron("40 16 * * *");

        //  更新失敗的檔案重新抓

        $schedule->call(function () {

            CrontabCenter::getInstance()->update_fail_daily_data();

        })->cron("45-59 16 * * *");

        $schedule->call(function () {

            CrontabCenter::getInstance()->update_fail_daily_data();

        })->cron("0-40 17 * * *");

        // 轉存基本股價資料

        $schedule->call(function () {

            CrontabCenter::getInstance()->auto_save_this_month_file_to_db();

        })->cron("45 17 * * *");

        // 自動建立技術指標初始資料

        $schedule->call(function () {

            CrontabCenter::getInstance()->create_init_data();

        })->cron("50,55 17 * * *");

        //  KD

        $schedule->call(function () {

            CrontabCenter::getInstance()->count_KD();

        })->cron("* 18 * * *");

        //  RSI

        $schedule->call(function () {

            CrontabCenter::getInstance()->count_RSI();

        })->cron("* 19 * * *");

        //  MACD

        $schedule->call(function () {

            CrontabCenter::getInstance()->count_MACD();

        })->cron("* 20 * * *");

       //  布林

       $schedule->call(function () {

           CrontabCenter::getInstance()->count_Bollinger();

       })->cron("* 21 * * *");


       // 自動計算買賣壓力

       $schedule->call(function () {

           CrontabCenter::getInstance()->count_sellBuyPercent();

       })->cron("* 22 * * *");

        $schedule->call(function () {

            CrontabCenter::getInstance()->count_sellBuyPercent();

        })->cron("0-15 23 * * *");


       // 透過Line自動回報選股條件

       $schedule->call(function () {

           CrontabCenter::getInstance()->BollingerBuy();

       })->cron("15 23 * * *");

       $schedule->call(function () {

            CrontabCenter::getInstance()->BollingerSell();

       })->cron("20 23 * * *");

        // 自動建立空白檔案

        $schedule->call(function () {

            CrontabCenter::getInstance()->create_empty_file();

        })->cron("* * 1 * *");

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
