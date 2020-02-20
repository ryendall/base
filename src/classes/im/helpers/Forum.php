<?php
namespace im\helpers;
use \Exception;

class Forum {

    /**
     * Convert html markup to phpBB format
     */
    public static function htmlToBb(string $text) {
        // anchor tags for relative urls
        $text = preg_replace('#<a href="(\/[^"]+)">([^<]+)</a>#','[url=https://www.imutual.co.uk$1]$2[/url]',$text);
        // anchor tags for absolute urls
        $text = preg_replace('#<a href="([^"]+)">([^<]+)</a>#','[url=$1]$2[/url]',$text);
        // bold tags
        $text = preg_replace('#<(?>b|strong)>([^<]+)<\/(?>b|strong)>#','[b]$1[/b]',$text);
        // line breaks
        $text = preg_replace('#<br\/?\s?>#',"\n",$text);
        return strip_tags($text); // remove any other tags
    }

}
