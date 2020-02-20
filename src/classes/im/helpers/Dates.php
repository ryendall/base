<?php
namespace im\helpers;

class Dates {

	/**
	 * Determine if one date string is greater than another
     * Return 1 if first is greater, -1 if second is greater, 0 if equal
	 *
	 * @param   string   $date1
	 * @param   string   $date2
	 * @return  int
	 */
	public static function compareDates($date1,$date2) {
        if (!$time1 = strtotime($date1) ) throw new Exception($date1.' is not a valid date');
        if (!$time2 = strtotime($date2) ) throw new Exception($date2.' is not a valid date');
        if ( $time1 > $time2 ) return 1;
        elseif ( $time2 > $time1 ) return -1;
        else return 0; // they are equal
    }
}
