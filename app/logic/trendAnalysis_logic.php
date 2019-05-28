<?php

namespace App\logic;

class trendAnalysis_logic
{

    public function count( $data )
    {

        $result = [];

        if ( !empty($data) && is_array($data) )
        {

            $result = collect( $data )->filter(function ($item){
                $close = collect($item)->pluck("close")->values()->toArray();
                $matchTargetNumber = 0;
                foreach ($close as $key => $row)
                {
                    if ( isset($close[$key+1]) )
                    {
                        $matchTargetNumber += $close[$key+1] > $row ? 1 : 0 ;
                    }
                }
                return $matchTargetNumber >= 2;
            })->toArray();

        }

        return $result;

    }

    public static function getInstance()
    {

        return new self;

    }

}
