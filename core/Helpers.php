<?php

namespace app\core;

abstract class Helpers {
    public static function generateToken()
    {
        $token = openssl_random_pseudo_bytes(16);
        $token = bin2hex($token);
        return $token;
    }

    public static function sameString($strA = "", $strB = "")
    {
        $shorter = mb_strlen($strA) < mb_strlen($strB) ? $strA : $strB;
        $longer = $shorter === $strA ? $strB : $strA;

        $same = "";

        for ($i = 0; $i < mb_strlen($shorter); $i++) {
            if ($shorter[$i] === $longer[$i]) $same .= $shorter[$i];
            else break;
        }

        return $same;
    }

    public static function removeSameString($strA = "", $strB = "")
    {
        $diff = self::sameString($strA, $strB);
        $strA = mb_substr($strA, mb_strlen($diff));
        $strB = mb_substr($strB, mb_strlen($diff));
        return [$strA, $strB];
    }

    public static function clearPath($path = "")
    {
        return preg_replace("#\\\+|/{2,}#", "/", $path);
    }

    public static function isLinkExternal($link = '')
    {
        $link = mb_strtolower($link);
        $protoPos = mb_strpos($link, "://");
        if (!$protoPos) return false;
        $protocols = ['https', 'http', 'ftps', 'ftp', 'webdav'];
        $proto = mb_substr($link, 0, $protoPos);
        if (in_array($proto, $protocols)) return true;
        else return false;
    }

    public static function getFile($path = '')
    {
        if ($path == '') return null;
        if (!self::isLinkExternal($path)) {
            if (mb_strpos($path, Application::$app->paths['SITE_ROOT']) === false) $path = Application::$app->paths['SITE_ROOT'] . "/{$path}";
            if (!file_exists($path)) return null;
            return self::getFileByFopen($path);
        }

        $byFopen = true;
        $proto = strtolower($path);
        $proto = substr($path, 0, 5);
        if (!ini_get('allow_url_fopen')) $byFopen = false;
        else if ($proto === 'https' && !extension_loaded('openssl')) $byFopen = false;
        if ($byFopen) return self::getFileByFopen($path);

        $byCURL = true;
        if (!extension_loaded('curl')) $byCURL = false;
        if ($byCURL) return self::getFileByCURL($path);

        $byFSockOpen = true;
        if (!function_exists("fsockopen")) $byFSockOpen = false;
        if ($byFSockOpen) return self::getFileByFSockOpen($path);
        return '';
    }

    private static function getFileByFopen($path)
    {
        return file_get_contents($path);
    }

    private static function getFileByCURL($path)
    {
        $userAgent = (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] != '') ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = [
            CURLOPT_CUSTOMREQUEST => "GET", // set request type post or get
            CURLOPT_POST => false, // set to GET
            CURLOPT_USERAGENT => $userAgent, // set user agent
            CURLOPT_COOKIEFILE => "cookie.txt", // set cookie file
            CURLOPT_COOKIEJAR => "cookie.txt", // set cookie jar
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER => false, // don't return headers
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_ENCODING => "", // handle all encodings
            CURLOPT_AUTOREFERER => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
            CURLOPT_TIMEOUT => 120, // timeout on response
            CURLOPT_MAXREDIRS => 10 // stop after 10 redirects
        ];

        $ch = curl_init($path);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $content;
    }

    private static function getFileByFSockOpen($path)
    {
        // ! TODO finish this
        $fp = fsockopen($path, 80);
        $content = '';

        if ($fp) {
            fwrite($fp, "GET / HTTP/1.1\r\nHOST: slashdot.org\r\n\r\n");

            while (!feof($fp)) {
                $content .= fread($fp, 256);
            }

            fclose ($fp);
            return $content;
        } else {
            return '';
        }
    }

    public static function arrayKeyPos($array, $key)
    {
        $keys = array_keys($array);
        return array_search($key, $keys);
    }

    public static function flattenArray($array, $preserveKeys = false, $renameExisting = false)
    {
        $flatArray = [];
        array_walk_recursive($array, function($value, $key) use (&$flatArray, $preserveKeys, $renameExisting) {
            if ($preserveKeys === true) {
                if ($renameExisting === true) {
                    if (isset($flatArray[$key])) {
                        $index = 1;
                        while (isset($flatArray[$key . "_{$index}"])) ++$index;
                        $key = "{$key}_{$index}";
                    }
                    $flatArray[$key] = $value;
                } else $flatArray[$key] = $value;
            } else $flatArray[] = $value;
        });
        return $flatArray;
    }

    public static function removeElementFromArray(&$array, $element)
    {
        if (($position = array_search($element, $array)) !== false) {
            $array = array_splice($array, $position - 1, 1);
        }
        return $array;
    }

    public static function insertElementToArrayAtPos(&$array, $pos, $element)
    {
        $array = array_splice($array, $pos, 0, $element);
        return $array;
    }
}