<?php

namespace App\logic;

use App\model\Holiday;

class Holiday_logic
{

	// 取得指定日期之前X日的工作日 or 之後 X日的工作日

	public function get_work_date( int $before_days, string $now_date, $type = 1 )
	{

		$result = '';

		if ( !empty($before_days) && is_int($before_days) && !empty($now_date) )
		{

			$limit = $before_days;

			$Holiday = new Holiday();

			$Holiday_array = $Holiday->get_list()->pluck("holiday_date")->toArray();

			$day = strtotime($now_date);

			while ( $limit > 0 )
			{

				if ( $type === 1 )
				{

					$day -= 86400;

				}
				else
				{

					$day += 86400;

				}

				if ( !in_array( date("Y-m-d", $day), $Holiday_array ) && !in_array( (int)date("w", $day), [0 ,6] ) )
				{

					$limit--;

				}

				if ( $day <= mktime( 0, 0, 0, 1, 1, 2016 ) )
				{

					break;

				}

			}

			$result = date("Y-m-d", $day);

		}

		return $result;

	}

	//  當日是否為假日

    public function is_holiday( int $date )
    {

        $result = false;

        try
        {

            $Holiday = new Holiday();

            $Holiday_array = $Holiday->get_list()->pluck("holiday_date")->toArray();

            if ( in_array( date("Y-m-d", $date), $Holiday_array ) )
            {

                throw new \Exception(true);

            }

            if ( in_array( (int)date("w", $date), [0 ,6] ) )
            {

                throw new \Exception(true);

            }

        }
        catch (\Exception $e)
        {

            $result = true;

        }

        return $result;

    }


    public static function getInstance()
    {

        return new self;

    }

}
