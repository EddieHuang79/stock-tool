<?php

namespace App\query;

use App\logic\SellBuyPercent_logic;

class countAssignSellBuyPercent
{

    // 		自動計算買賣壓力
    /*

            沒資料的情況下，最多一次執行兩隻股票

    */

    public function auto_count_SellBuyPercent()
    {

//        $code_list = [1101,1103,1110,1203,1213,1216,1219,1225,1229,1235,1236,1315,1316,1324,1325,1410,1413,1418,1419,1432,1435,1436,1438,1439,1441,1443,1449,1453,1454,1456,1457,1465,1466,1470,1472,1475,1516,1529,1535,1538,1541,1583,1603,1615,1617,1713,1726,1735,1776,1805,1903,2008,2012,2025,2027,2028,2033,2102,2115,2206,2231,2243,2303,2317,2321,2324,2327,2348,2354,2364,2748,9110,9157,9188,9918,9926,9928,9929,9931,1240,1258,1259,1264,1268,1333,1566,1570,1584,1591,1593,1595,1599,1742,1777,1788,1796,1813,4131,4152,5011,5016,5202,5205,5206,5209,5210,5212,6026,6101,6103,6130,7402,8032,8047,8067,8077 ];
        $code_list = [1103,1110,1203,1213,1216,1219,1225,1229,1235,1236,1315,1316,1324,1325,1410,1413,1418,1419,1432,1435,1436,1438,1439,1441,1443,1449,1453,1454,1456,1457,1465,1466,1470,1472,1475,1516,1529,1535,1538,1541,1583,1603,1615,1617,1713,1726,1735,1776,1805,1903,2008,2012,2025,2027,2028,2033,2102,2115,2206,2231,2243,2303,2317,2321,2324,2327,2348,2354,2364,2748,9110,9157,9188,9918,9926,9928,9929,9931,1240,1258,1259,1264,1268,1333,1566,1570,1584,1591,1593,1595,1599,1742,1777,1788,1796,1813,4131,4152,5011,5016,5202,5205,5206,5209,5210,5212,6026,6101,6103,6130,7402,8032,8047,8067,8077 ];

        // 取得所有股票

        collect($code_list)->map(function ($code) {

            SellBuyPercent_logic::getInstance()->count_data_logic( $code );

        });

        return true;

    }

    public static function getInstance()
    {

        return new self;

    }

}
