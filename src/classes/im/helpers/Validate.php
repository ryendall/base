<?php
namespace im\helpers;

class Validate {

	/**
	 * Checks string is valid date in YYYY-MM-DD format
	 *
	 * @param   string   $date
	 * @return  bool
	 */
	public static function dbDate($date,$exceptionOnError=false) {
        $tmp=explode('-',$date);
        if ( !empty($tmp[2]) && checkdate($tmp[1], $tmp[2], $tmp[0]) ) {
            return $date;
        }
        if ( $exceptionOnError ) self::throwException('Invalid dbDate: '.$date);
        else return false;
    }

	/**
	 * Checks string is valid uuid v4
	 *
	 * @param   string   $string
	 * @return  bool
	 */
	public static function uuid(string $string,$exceptionOnError=false) {
        if ( preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $string)) {
            return $string;
        }
        if ( $exceptionOnError ) self::throwException('Invalid uuid: '.$string);
        else return false;
    }

	public static function ukDateTime($datetime, $sep='/',$exceptionOnError=false) {
        if ( preg_match('#([\d]{1,2})'.$sep.'([\d\w]{1,3})'.$sep.'([\d]{2,4})( |T)?([\d:+]*)#',$datetime,$match) ) {
            if ( $time = strtotime($match[3].'-'.$match[2].'-'.$match[1].$match[4].$match[5]) ) {
                return date('Y-m-d H:i:s',$time);
            }
        }
        if ( $exceptionOnError ) self::throwException('Invalid UK date: '.$datetime);
        else return false;
    }

	public static function usDateTime($datetime, $sep='/',$exceptionOnError=false) {
        if ( preg_match('#([\d]{1,2})'.$sep.'([\d\w]{1,3})'.$sep.'([\d]{2,4})( |T)?([\d:+]*)#',$datetime,$match) ) {
            if ( $time = strtotime($match[3].'-'.$match[1].'-'.$match[2].$match[4].$match[5]) ) {
                return date('Y-m-d H:i:s',$time);
            }
        }
        if ( $exceptionOnError ) self::throwException('Invalid US date: '.$datetime);
        else return false;
    }

    public static function positiveInteger($var,$exceptionOnError=false) {
        if ( filter_var($var, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) !== false ) {
            return intval($var);
        }
        if ( $exceptionOnError ) self::throwException('Invalid posInt: '.$var);
        else return false;
    }

    public static function numeric($var,$exceptionOnError=false) {
        if ( is_numeric($var) ) {
            return floatval($var);
        }
        if ( $exceptionOnError ) self::throwException('Invalid number: '.$var);
        else return false;
    }

    public static function url($var,$exceptionOnError=false) {
        if ( filter_var($var, FILTER_VALIDATE_URL) !== false ) return $var;
        if ( $exceptionOnError ) self::throwException('Invalid url: '.$var);
        else return false;
    }

    protected static function throwException($text,$code=400) {
        throw new Exception($text,$code);
    }
}
