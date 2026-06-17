<?php

namespace AppBundle\Util;

use Carbon\CarbonPeriod;
use Cmixin\BusinessDay;

class BusinessDays
{
    public static function numOfBusniessDays($startDate, $endDate)
    {
        BusinessDay::enable('Carbon\Carbon', 'gb-engwales');

        $period = CarbonPeriod::between($startDate, $endDate);

        $weekendFilter = function ($date) {
            return !$date->isWeekend();
        };
        $holidayFilter = function ($date) {
            return !$date->isHoliday();
        };
        
        $period->filter($weekendFilter);
        $period->filter($holidayFilter);
                
        return $period->count();
    }
}
