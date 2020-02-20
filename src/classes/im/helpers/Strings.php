<?php
namespace im\helpers;
use \Exception;

class Strings {

    // @todo https://stackoverflow.com/questions/1176904/php-how-to-remove-all-non-printable-characters-in-a-string
    public static function stripBadCharacters($text) {
        //return preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $text);
        return preg_replace('~[^\P{Cc}\r\n]+~u', '', $text);
    }

	/**
	 * Ensure string is UTF-8 encoded
	 *
	 * @param   string   $text      Original text
	 * @return  string   $encoded   UTF-8 encoded text
	 */
    public static function utf8Encode($text) {
        $encoding = mb_detect_encoding($text, 'UTF-8', true);
        if ( $encoding != 'UTF-8' ) {
            $text = ( $encoding ) ? iconv($encoding, 'UTF-8', $text) : utf8_encode($text);
        }
        return $text;
    }

	/**
	 * Remove specified params from query string
	 *
	 * @param   array    $changes   key/value pairs to add/change/remove
	 * @param   string   $url       Original url
	 * @return  string   $new_url   Modified url
	 */
	public static function modifyQueryString(array $changes, string $url=null) {
        // Parse the url into pieces
        if (empty($url)) $url = $_SERVER['REQUEST_URI'];
        $url_array = parse_url($url);
        if ( $url_array === false || empty($url_array['path']) ) {
            throw new Exception('Could not parse url');
        }

        // The original URL had a query string, modify it.
        if(!empty($url_array['query'])){
            parse_str($url_array['query'], $query_array);
            foreach ($changes as $key => $value) {
                if ( $value === \NULL && isset($query_array[$key]) ) {
                    // remove this parameter
                    unset($query_array[$key]);
                } elseif ( $value !== \NULL ) {
                    // change/add
                    $query_array[$key] = $value;
                }
            }
        } else {
            // The original URL didn't have a query string, add it.
            $query_array = array_filter($changes, 'strlen');
        }
        $new_url = $url_array['path'];
        if ( !empty($url_array['host']) ) {
            $scheme = $url_array['scheme'] ?? 'https';
            $new_url = $scheme.'://'.$url_array['host'].$new_url;
        }
        if ( !empty($query_array) ) {
            $new_url .= '?'.http_build_query($query_array);
        }
        return $new_url;
    }

	/**
	 * Add specified string to the path of supplied url
	 *
	 * @param   string   $path      text to add to existing url path
	 * @param   string   $url       Original url
	 * @return  string   $new_url   Modified url
	 */
	public static function addToPath(string $path, string $url=null) {
        throw new Exception('addToPath method not yet written');
        return $new_url;
    }


    /**
     * If domain like SUBDOMAIN.DOMAIN.co.uk or SUBDOMAIN.DOMAIN.com, return w/o subdomain part
     * else return false
     */
	public static function hasParentDomain(string $text) {
        if ( preg_match('/[\w\d\-]+\.([\w\d\-]+\.(com|co.uk))$/', $text, $match) ) {
            return $match[1];
        }
        return false;
    }

    /**
     * Return array of domain names found in text
     */
	public static function getHostsFromText(string $text) {
        preg_match_all('/(:\/\/|@)([\w\d\-]+\.[\w\d\-\.]+[\w\d\-])/', $text, $matches);
        return $matches[2] ?? [];
    }

    /**
     * Return portion of string after last occurence of phrase
     */
	public static function textAfterPhrase(string $text, string $phrase, bool $anyCase=true) {
        $phpFunction = ( $anyCase ) ? 'strripos' : 'strrpos';
        $pos = $phpFunction($text,$phrase);
        if ( $pos === false ) {
            return $text;
        } else {
            return substr($text, $pos+strlen($phrase));
        }
    }

    /**
     * Return hostname element from web address
     * or null if invalid url
     */
	public static function getHostFromUrl(string $url = null) {
        $bits=parse_url($url);
        return $bits['host'] ?? null;
    }

	/**
	 * Extract meta tag content from a web page
	 *
	 * @param   string   $html
	 * @return  array    $result
	 */
	public static function getMetaContentFromHtml(string $html) {

        $doc = new DOMDocument();
        libxml_use_internal_errors(true); // suppress warnings about invalid html
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        $title = $xpath->evaluate('string(//head/title)');
        if ( empty($title) ) {
            $title = $xpath->evaluate('string(//meta[@property="og:title"]/@content)');
        }

        $description = $xpath->evaluate('string(//meta[@name="description"]/@content)');
        if ( empty($description) ) {
            // Check for sentence case version
            $description = $xpath->evaluate('string(//meta[@name="Description"]/@content)');
        }
        $keywords = $xpath->evaluate('string(//meta[@name="keywords"]/@content)');
        if ( empty($keywords) ) {
            // Check for sentence case version
            $keywords = $xpath->evaluate('string(//meta[@name="Keywords"]/@content)');
        }

        $result = [
            'meta_title' => trim(self::stripBadCharacters(self::utf8Encode($title))),
            'meta_description' => trim(self::stripBadCharacters(self::utf8Encode($description))),
            'meta_keywords' => trim(self::stripBadCharacters(self::utf8Encode($keywords))),
        ];

        return $result;

    }

    /**
	 * Convert string to list of unique words, ignoring small / common ones
	 *
	 * @param   string   $text
	 * @return  array    $words
	 */
	public static function stringToUniqueKeywords(string $text) {

        $words=[];
        $text = preg_replace('/[^\w\-\s]/',' ',$text);
        foreach(array_map('trim',explode(' ',$text)) as $word) {
            if ( strlen($word) > 3 && !in_array($word,$words) ) {
                $words[]=$word;
            }
        }
        return $words;
    }

    /**
	 * Look for given words in string
	 *
	 * @param   string   $text
	 * @return  array    $words array key is the word, value is match count e.g. ['MyWord'=>2 ]
	 */
	public static function findWordsInText(string $text, array $words) {

        $matches=[];
        $words=array_map('strtolower',$words);
        $text = trim(strtolower($text)); // convert to lowercase
        $text = preg_replace('/[\s]+/',' ',$text); // convert multiple spaces to single
        $text = preg_replace('/[^\w\-\s]/','',$text); // remove unwanted chrs

        foreach(explode(' ',$text) as $word) {
            if ( in_array($word,$words) ) {
                if ( isset($matches[$word]) ) {
                    $matches[$word]++;
                } else {
                    $matches[$word]=1;
                }
            }
        }
        return $matches;
    }

    /**
	 * Convert monetary string into array of amount and currency
	 *
	 * @param   string   $text
	 * @return  array    $commission
	 */
	public static function stringToCommission(string $text) {

        $comm=['revenue'=>null,'revenue_type'=>'fixed','currency_id'=>null];
        $currencies=['p'=>1, 'pence'=>1, '£'=>1, 'GBP'=>1,  '$'=>2, 'USD'=>2, 'EUR'=>3, 'DKK'=>4, 'SEK'=>5, 'NOK'=>6, 'AUD'=>7];
        if ( !preg_match('/([^\d]*)\s*([\d,.]+)\s*([^\d]*)/',trim($text),$matches) ) {
            throw new Exception('Not a recognised money format: '.$text);
        }
        $prefix = $matches[1];
        $amount = $matches[2];
        $suffix = $matches[3];

        // validate prefix
        if ( !empty($prefix) ) {
            if ( !isset($currencies[$prefix]) ) {
                throw new Exception('Unrecognised money prefix '.$prefix.' in: '.$text);
            } else {
                $comm['currency_id'] = $currencies[$prefix];
            }
        }
        // validate suffix
        if ( !empty($suffix) ) {
            if ( $suffix == '%' ) {
                $comm['revenue_type'] = 'percentage';
            } elseif ( !isset($currencies[$suffix]) ) {
                throw new Exception('Unrecognised money suffix '.$suffix.' in: '.$text);
            } elseif ( !empty($comm['currency_id']) && $comm['currency_id'] != $currencies[$suffix] ) {
                throw new Exception('Currency ambiguity ('.$prefix.' vs '.$suffix.') in: '.$text);
            } else {
                $comm['currency_id'] = $currencies[$suffix];
            }
        }
        // validate amount
        $comma_position = strpos($amount,',');
        if ( $comma_position !== false ) {
            // remove/replace commas
            $dot_position = strpos($amount,',');
            if ( $dot_position !== false ) {
                if ( $comm['currency_id'] == 2 ) {
                    if ( $dot_position > $comma_position ) {
                        throw new Exception('Dot after comma in euro amount: '.$text);
                    }
                    $amount=str_replace('.','',$amount);
                 } else { // non-euro
                    if ( $comma_position > $dot_position ) {
                        throw new Exception('Comma after dot in non-euro amount: '.$text);
                    }
                    $amount=str_replace(',','',$amount);
                }
            }
        }
        if ( !is_numeric($amount) ) {
            throw new Exception('Non-numeric amount '.$amount.' in '.$text);
        }
        $comm['revenue']=floatval($amount);
        if ( $comm['revenue_type'] == 'fixed' && !in_array($suffix, ['p','pence']) ) $comm['revenue'] *= 100; // convert to pence

        return $comm;
    }

    /**
	 * Does string contain phrase implying closure of program
	 *
	 * @param   string   $text
	 * @return  bool
	 */
	public static function nameSuggestsClosure(string $text) {
        return preg_match('/(\s|\[|\(){1}(clos|paus)(ed|ing)/i',$text);
    }

    /**
	 * Replace {VAR} placeholders in string with values from supplied array
	 *
	 * @param   string   $text
	 * @param   array    $values
	 * @return  string
	 */
    public static function populatePlaceholders(string $string,array $values,bool $mustReplaceAll=true) {
        preg_match_all('|(\{)(\w+)(\})|', $string, $matches);
        if ( !empty($matches[2]) ) {
            foreach(array_unique($matches[2]) as $var) {
                $value = $values[strtolower($var)] ?? null;
                if ( $value === null ) {
                    if ( $mustReplaceAll ) throw new Exception('No value for {'.$var.'}');
                } else {
                    $string = str_replace('{'.$var.'}',$value,$string);
                }
            }
        }
        return $string;
    }

    public static function csvToArray(string $text, array $options=[]) {
        // First write content to temp file, so we can then use fgetcsv
        $tmpfname = tempnam('/tmp', 'csv');
        \file_put_contents($tmpfname,$text);
        $handle = fopen($tmpfname, "r");

        $expected_items = null;
        if ( isset($options['header']) ) {
            $header = fgetcsv($handle);
            if ( $header !== $options['header'] ) {
                if ( count($header) == 1 ) {
                    $msg = 'Unexpected header: '.json_encode($header);
                } else {
                    $msg = 'Header line differences: '.print_r(array_diff($options['header'],$header),true);
                }
                throw new Exception($msg);
            }
            $expected_items = count($options['header']);
        }

        $data=[];
        while ($items = fgetcsv($handle)) {
            if ( !empty($items) ) {
                if ( $expected_items ) {
                    // Compare header against line items
                    if ( count($items) != $expected_items ) {
                        trigger_error('Expected '.$expected_items.' items in '.json_encode($items));
                        continue;
                    }
                    // Add data with headers as array keys
                    $data[]=array_combine($options['header'],$items);
                } else {
                    $data[]=$items;
                }
            }
        }

        fclose($handle);
        unlink($tmpfname);
        return $data;
    }

}
