<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\logic\Crontab_logic;

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

        if ( env("APP_DEBUG") === false && env("APP_ENV") === 'local' ) 
        {

            // 自動取得資料

            $schedule->call(function () {

                // 從股票代號0開始

                Crontab_logic::auto_get_data( 1 );

                // 從股票代號4000開始

                Crontab_logic::auto_get_data( 2 );

                // 從股票代號5000開始

                Crontab_logic::auto_get_data( 3 );

                // 從股票代號6000開始

                Crontab_logic::auto_get_data( 4 );

                // 從股票代號7000開始(只有1筆)
                // 從股票代號6600開始

                Crontab_logic::auto_get_data( 5 );

                // 從股票代號8000開始

                Crontab_logic::auto_get_data( 6 );

                // 從股票代號9000開始

                Crontab_logic::auto_get_data( 7 );

            })
            ->cron("* * * * *");


            // 刪除空白檔案

            // $schedule->call(function () {

            //     Crontab_logic::delete_empty_file();

            // })
            // ->cron("5 * * * *");


            // 轉存基本股價資料

            $schedule->call(function () {

                Crontab_logic::auto_save_file_to_db();

            })
            ->cron("5 */1 * * *");


            // 自動計算買賣壓力

            $schedule->call(function () {

                Crontab_logic::auto_count_SellBuyPercent();

            })
            ->cron("2,7,12,17,22,27,32,37,42,47,52,57 * * * *");


            // 自動計算技術指標

            $schedule->call(function () {

                // RSV

                Crontab_logic::auto_count_technical_analysis( 1 );

                // KD

                Crontab_logic::auto_count_technical_analysis( 2 );

                // RSI

                Crontab_logic::auto_count_technical_analysis( 4 );

                // MACD

                Crontab_logic::auto_count_technical_analysis( 6 );

            })
            ->cron("1,6,11,16,21,26,31,36,41,46,51,56 * * * *");


            // 自動更新所有股票的當日資料

            $schedule->call(function () {

                Crontab_logic::update_daily_data();

                Crontab_logic::auto_save_this_month_file_to_db();

            })
            ->cron("*/3 14,15,16 * * *");

            // 透過Line自動回報選股條件

            $schedule->call(function () {

                // 策略1回報

                Crontab_logic::daily_info( 1 );

            })
            ->cron("30 17 * * *");


        }

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
