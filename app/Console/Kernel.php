<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\jobs\CreateInitFile;
use App\jobs\KDStrategyJobs;
use App\jobs\CountTechnicalAnalysis;
use App\jobs\AccessCSV;
use App\jobs\SaveFromCSV;
use App\jobs\CountSellBuyPercent;
use App\jobs\SyncFromStockData;
use App\jobs\BollingerBandsStrategySimulation9;
use App\jobs\BollingerBandsStrategyBuyingJobs;
use App\jobs\BollingerBandsStrategySellingJobs;
use App\jobs\BollingerBandsStrategyGetAssignStock;
use App\logic\Holiday_logic;
use App\query\updateNoDataStock;


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

            // 自動取得資料

            // $schedule->call(function () {

            //     // 從股票代號0開始

            //     AccessCSV::getInstance()->auto_get_data( 1 );

            //     // 從股票代號4000開始

            //     AccessCSV::getInstance()->auto_get_data( 2 );

            //     // 從股票代號5000開始

            //     AccessCSV::getInstance()->auto_get_data( 3 );

            //     // 從股票代號6000開始

            //     AccessCSV::getInstance()->auto_get_data( 4 );

            //     // 從股票代號7000開始(只有1筆)
            //     // 從股票代號6600開始

            //     AccessCSV::getInstance()->auto_get_data( 5 );

            //     // 從股票代號8000開始

            //     AccessCSV::getInstance()->auto_get_data( 6 );

            //     // 從股票代號9000開始
            //     // 從股票代號8500開始
            //     // 從股票代號4700 - 5000

            //     AccessCSV::getInstance()->auto_get_data( 7 );

            // })
            // ->cron("* * * * *");



            // 轉存基本股價資料

//            $schedule->call(function () {

                // SaveFromCSV::getInstance()->auto_save_file_to_db( 1 );

                // SaveFromCSV::getInstance()->auto_save_file_to_db( 2 );

                // SaveFromCSV::getInstance()->auto_save_file_to_db( 3 );

                // SaveFromCSV::getInstance()->auto_save_file_to_db( 4 );

                // SaveFromCSV::getInstance()->auto_save_file_to_db( 5 );

                // SaveFromCSV::getInstance()->auto_save_file_to_db( 6 );

                // SaveFromCSV::getInstance()->auto_save_file_to_db( 7 );

                // SaveFromCSV::getInstance()->auto_save_file_to_db( 8 );

                // SaveFromCSV::getInstance()->auto_save_file_to_db( 9 );

//            })
//            ->cron("0,5 18 * * *");

        //            // 策略計算
//
//            $schedule->call(function () {
//
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//                sleep(5);
//                BollingerBandsStrategySimulation9::getInstance()->do();
//
//            })->cron("* 12,13,14,15 * * *");


            //  取得假日設定

            $is_holiday = Holiday_logic::getInstance()->is_holiday( time() );

            if ( $is_holiday === true ) {
                return;
            }

            // 自動更新所有股票的當日資料

            $schedule->call(function () {

                AccessCSV::getInstance()->update_daily_data( 1 );

                AccessCSV::getInstance()->update_daily_data( 2 );

                AccessCSV::getInstance()->update_daily_data( 3 );

                AccessCSV::getInstance()->update_daily_data( 4 );

                AccessCSV::getInstance()->update_daily_data( 5 );

                AccessCSV::getInstance()->update_daily_data( 6 );

                AccessCSV::getInstance()->update_daily_data( 7 );

                AccessCSV::getInstance()->update_daily_data( 8 );

                AccessCSV::getInstance()->update_daily_data( 9 );

                AccessCSV::getInstance()->update_daily_data( 10 );

                AccessCSV::getInstance()->update_daily_data( 11 );

                AccessCSV::getInstance()->update_daily_data( 12 );

            })->cron("* 14,15,16 * * *");

            // 轉存基本股價資料

            $schedule->call(function () {

                SaveFromCSV::getInstance()->auto_save_this_month_file_to_db();

                sleep(3);

                updateNoDataStock::getInstance()->update();

            })->cron("45 16 * * *");

            // 自動建立技術指標初始資料

            $schedule->call(function () {

                SyncFromStockData::getInstance()->create_init_data();

            })->cron("50,55 16 * * *");

            //  KD

            $schedule->call(function () {

                CountTechnicalAnalysis::getInstance()->auto_count_technical_analysis( 1 );

            })->cron("* 17 * * *");

            //  RSI

            $schedule->call(function () {

                CountTechnicalAnalysis::getInstance()->auto_count_technical_analysis( 2 );

            })->cron("* 18 * * *");

            //  MACD

            $schedule->call(function () {

                CountTechnicalAnalysis::getInstance()->auto_count_technical_analysis( 3 );

            })->cron("* 19 * * *");

           //  布林

           $schedule->call(function () {

               CountTechnicalAnalysis::getInstance()->auto_count_technical_analysis( 4 );

           })->cron("* 20 * * *");


           // 自動計算買賣壓力

           $schedule->call(function () {

               CountSellBuyPercent::getInstance()->auto_count_SellBuyPercent();

           })->cron("* 21 * * *");

           // 透過Line自動回報選股條件

           $schedule->call(function () {

               BollingerBandsStrategyBuyingJobs::getInstance()->count();

           })->cron("0 22 * * *");

           $schedule->call(function () {

//                   BollingerBandsStrategySellingJobs::getInstance()->count();
               BollingerBandsStrategyGetAssignStock::getInstance()->count();


           })->cron("10 22 * * *");


            // 自動建立空白檔案

            $schedule->call(function () {

                CreateInitFile::getInstance()->create_init_file();


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
