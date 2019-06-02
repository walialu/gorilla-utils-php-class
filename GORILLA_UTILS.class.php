<?php
// duct-tape php-fpm
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

class GORILLA_UTILS
{
    private $config;
    private $database;
    public function __construct($config = [])
    {
        $this->config = $config;
    }
    public function db()
    {
        if (!$this->database) {
            $this->database = new mysqli(
                $this->config['mysql_host'],
                $this->config['mysql_username'],
                $this->config['mysql_password'],
                $this->config['mysql_database'],
                $this->config['mysql_port']
            );
            if ($this->database->connect_errno) {
                die('Could not connect to database.');
            }
            if (!$this->database->set_charset("utf8")) {
                printf("Error setting charset to utf8: %s\n", $this->database->error);
                exit();
            }
            if (!$this->database->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                printf("Error setting names and collate to utf8mb4: %s\n", $this->database->error);
                exit();
            }
        }
        return $this->database;
    }

    public function getMySQLDateFormatted($mysqlDate = '2009-11-05 08:13:00', $fmtString = 'Y/m/d')
    {
        return date($fmtString, strtotime($mysqlDate));
    }


    public static function getRequestBody($parseJson = true)
    {
        $body = file_get_contents('php://input');
        if ($parseJson) {
            if (!$body) {
                return [];
            }
            return json_decode($body, true);
        } else {
            if (!$body) {
                return "";
            }
            return $body;
        }
    }

    public static function getGetParam($key, $defaultValue = null, $safeGuard = false)
    {
        $retval = $defaultValue;
        if (isset($_GET[$key])) {
            $retval = $_GET[$key];
            if ($safeGuard) {
                $retval = preg_replace('/[^\da-z_-]+/', '', $retval);
            }
        } else {
            $retval = $defaultValue;
        }
        return $retval;
    }

    public static function getGetParams($opts)
    {
        $retval = [];
        foreach ($opts as $opt) {
            $key = $opt[0];
            $defaultValue = (isset($opt[1])) ? $opt[1] : null;
            $safeGuard = (isset($opt[2])) ? $opt[2] : false;
            $retval[$key] = GORILLA_UTILS::getGetParam($key, $defaultValue, $safeGuard);
        }
        return $retval;
    }

    public static function getPostParam($key, $defaultValue = null, $safeGuard = false)
    {
        $retval = $defaultValue;
        if (isset($_POST[$key]) && strlen($_POST[$key]) > 0) {
            $retval = $_POST[$key];
        } else {
            $retval = $defaultValue;
            if ($safeGuard) {
                $retval = preg_replace('/[^\da-z_-]+/', '', $retval);
            }
        }
        return $retval;
    }

    public static function getPostParams($opts)
    {
        $retval = [];
        foreach ($opts as $opt) {
            $key = $opt[0];
            $defaultValue = (isset($opt[1])) ? $opt[1] : null;
            $safeGuard = (isset($opt[2])) ? $opt[2] : false;
            $retval[$key] = GORILLA_UTILS::getPostParam($key, $defaultValue, $safeGuard);
        }
        return $retval;
    }

    public static function getServerParam($key, $defaultValue = null, $safeGuard = false)
    {
        $retval = $defaultValue;
        if (isset($_SERVER[$key])) {
            $retval = $_SERVER[$key];
            if ($safeGuard) {
                $retval = preg_replace('/[^\da-z_-]+/', '', $retval);
            }
        } else {
            $retval = $defaultValue;
        }
        return $retval;
    }

    public static function getServerParams($opts)
    {
        $retval = [];
        foreach ($opts as $opt) {
            $key = $opt[0];
            $defaultValue = (isset($opt[1])) ? $opt[1] : null;
            $safeGuard = (isset($opt[2])) ? $opt[2] : false;
            $retval[$key] = GORILLA_UTILS::getServerParam($key, $defaultValue, $safeGuard);
        }
        return $retval;
    }

    public static function decodeUploadChunk($data)
    {
        $data = explode(';base64,', $data);
        if (!is_array($data) || !isset($data[1])) {
            return false;
        }
        $data = base64_decode($data[1]);
        if (!$data) {
            return false;
        }
        return $data;
    }

    public static function dbPrepareFields($fields)
    {
        $retval = '';
        $glue = ' = ?, ';
        $retval = implode($glue, $fields);
        $retval .= ' = ?';
        return $retval;
    }

    public static function JSON($success = null, $data = [], $opts = 'last')
    {
        $json = [];
        $opts = explode(',', $opts);
        if ($success !== null) {
            $json['success'] = $success;
        }
        $json = array_merge($json, $data);
        $retval = json_encode($json);
        if (in_array('no-echo', $opts)) {
            return $retval;
        } else {
            if (in_array('last', $opts)) {
                header('Content-Type: application/json');
                die($retval);
            } else {
                echo $retval;
            }
        }
    }

    public static function getWindowLocationHref()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST] $_SERVER[REQUEST_URI]";
    }

    public static function getRoute()
    {
        return $_SERVER['REQUEST_URI'];
    }

    public static function setNoCacheHeaders()
    {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    public static function redirectPermanent($url)
    {
        GORILLA_UTILS::setNoCacheHeaders();
        header("Location: $url", true, 301);
        die();
    }

    public static function notAllowed()
    {
        header("HTTP/1.1 401 Unauthorized");
        die('Not allowed');
    }

    public static function redirectTemporary($url)
    {
        GORILLA_UTILS::setNoCacheHeaders();
        header("Location: $url", true, 302);
        die();
    }

    # Credits to Gumbo
    # https://stackoverflow.com/questions/4114614/check-if-a-string-starts-with-a-number-in-php?rq=1#answer-4114620
    public static function startsWithNumber($string)
    {
        if (strlen($string) > 0 && ctype_digit(substr($string, 0, 1))) {
            return true;
        } else {
            return false;
        }
    }

    # Credits to Kendall Hopkins
    # https://stackoverflow.com/questions/2790899/how-to-check-if-a-string-starts-with-a-specified-string#answer-2790919
    public static function stringStartsWith($string, $query)
    {
        if (substr($string, 0, strlen($query)) === $query) {
            return true;
        } else {
            return false;
        }
    }
}
