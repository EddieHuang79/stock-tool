<?php

namespace App\logic;

use App\model\Holiday;

class Holiday_logic
{

	// 取得指定日期之前X日的工作日 or 之後 X日的工作日

	public function get_work_date( $before_days, $now_date, $type = 1 )
	{

		$result = '';

		if ( !empty($before_days) && is_int($before_days) && !empty($now_date) && !empty($now_date) )
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


    public static function getInstance()
    {

        return new self;

    }

}
