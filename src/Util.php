<?php

namespace Common;

/**
 * @package Util Class in PHP7
 * @author Jovanni Lo
 * @link https://github.com/lodev09/php-util
 * @copyright 2017 Jovanni Lo, all rights reserved
 * @license
 * The MIT License (MIT)
 * Copyright (c) 2017 Jovanni Lo
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

class Util {
    /**
     * get HTTP code name
     * @param $code HTTP code
     *
     * @return string
     * code name
    */
    public static function http_code($code) {
        $http_codes = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Switch Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not Extended'
        ];

        return isset($http_codes[$code]) ? $http_codes[$code] : 'Unknown code';
    }

    public static function get($field, $source = null, $default = null, $possible_values = []) {
        $source = is_null($source) ? $_GET : $source;
        if (is_array($source)) {
            $value = isset($source[$field]) ? $source[$field] : $default;
        } else if (is_object($source)) {
            $value = isset($source->{$field}) ? $source->{$field} : $default;
        }

        if ($possible_values) {
            $possible_values = is_array($possible_values) ? $possible_values : [$possible_values];
            return in_array($value, $possible_values) ? $value : $default;
        }

        return $value;
    }

    /**
     * get cli options/switches. If run via http, gets data from $_GET instead
     * @param  array $config   options configuration (extended). see php's getopt
     * @param  string &$message exception messages
     * @return array | boolean  returns false if not valid, returns array otherwise
     */
    public static function get_options($config, &$message = null) {
        if (!$config) return [];

        $config[] = 'help::';

        $short_opts = '';
        $long_opts = [];
        $required = [];
        $is_cli = self::is_cli();

        $options_map = [];
        foreach ($config as $option_raw) {
            if (!$option_raw) continue;

            $option_types = self::explode_clean($option_raw, ',');

            // base index to test if required, optional or no value
            $required_base_index = count($option_types) == 1 ? 0 : 1;

            $option = $option_types[$required_base_index];
            preg_match('/\:+/i', $option, $matches);

            $append = $matches ? $matches[0] : '';
            $is_required = strlen($append) == 1;

            $option_type_keys = [];
            foreach ($option_types as $index => $option_type) {
                $option_type = str_replace(':', '', $option_type);
                if (strlen($option_type) == 1) {
                    $short_opts .= $option_type.$append;
                } else {
                    $long_opts[] = $option_type.$append;
                }

                $option_type_keys[] = $option_type;
            }

            $option_key = $option_types[0];

            if ($is_required) {
                $required[] = $option_key;
            }

            $options_map[$option_key] = $option_type_keys;
        }

        if ($is_cli) {
            $result = getopt($short_opts, $long_opts);
        } else {
            $result = $_GET;
        }

        // generate the final values
        $values = [];
        foreach ($options_map as $key => $option_type_keys) {
            $value = null;
            // check for each keys if value is provided -- use the first one
            foreach ($option_type_keys as $option_key) {
                if (isset($result[$option_key])) {
                    $value = $result[$option_key];
                    break;
                }
            }

           if (!is_null($value)) $values[$key] = $value;
        }

        if (isset($result['help'])) {
            $fields = array_map(function($option_key) use ($is_cli, $required) {
                if ($option_key == 'help::') return '';

                $required_text = in_array($option_key, $required) ? ' (required)' : '';
                return $is_cli ? "\033[31m--$option_key\033[0m$required_text" : '<span class="text-danger">'.$option_key.'</span>';
            }, array_keys($options_map));;

            $message = 'Usage: php '.$_SERVER['SCRIPT_NAME'].' [options...]'.PHP_EOL;
            $message .= 'Options:'.PHP_EOL;
            $message .= "\t".trim(implode(PHP_EOL."\t", $fields));

            if (!$is_cli) $message = '<pre>'.$message.'</pre>';
            return false;
        }

        $validate = self::verify_fields($required, $values, $missing);
        if (!$validate) {
            $missing_fields = array_map(function($option_key) use ($is_cli) {
                return $is_cli ? "\033[31m$option_key\033[0m" : '<span class="text-danger">'.$option_key.'</span>';
            }, $missing);

            if ($missing_fields) {
                $plural = count($missing_fields) > 1;
                $message = self::implode_and($missing_fields).' field'.($plural ? 's' : '').' '.($plural ? 'are' : 'is').' required';
                if (!$is_cli) $message = '<pre>'.$message.'</pre>';
            }

            return false;
        } else return $values;
    }

    public static function is_pjax() {
        return isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] == true;
    }

    public static function in_string($needle, $string) {
        if (is_array($needle)) {
            return preg_match('/\b'.implode('\b|\b', $needle).'\b/i', $string) == 1;
        } else return stripos($string, $needle) !== false;
    }

    public static function hash($str) {
        return hash('sha256', $str);
    }

    public static function save_session_result($data) {
        $uuid = self::uuid();
        $_SESSION[$uuid] = json_encode($data);

        return $uuid;
    }

    public static function get_session_result($token) {
        $data = isset($_SESSION[$token]) ? $_SESSION[$token] : null;
        return json_decode($data);
    }

    public static function explode_ids($src, $separator = ';') {
        $text = is_array($src) ? implode(';', $src) : $src;
        $raw = preg_replace('/\s+/i', $separator, $text);
        return array_values(array_filter(explode($separator, $raw), function($id) {
            return is_numeric($id) && strlen($id);
        }));
    }

    public static function explode_clean($src, $separator = ';') {
        $text = is_array($src) ? implode($separator, $src) : $src;
        $raw = preg_replace('/\s+/i', $separator, $text);
        return array_values(array_filter(explode($separator, $raw), 'strlen'));
    }

    public static function implode_and($arr) {
        if (!is_array($arr)) return $arr;
        $first_key = key(array_slice($arr, 0, 1, TRUE));
        $last_key = key(array_slice($arr, -1, 1, TRUE));
        $result = '';
        foreach ($arr as $key => $item) {
            if ($first_key == $key) $separator = '';
            else $separator = $last_key == $key ? ' and' : ',';
            $result .= $separator.' '.$item;
        }

        return ltrim($result);
    }
    /**
     * encode and print the result to json (used for ajax routines)
     * @param  string $status  status
     * @param  string $message message
     * @param  mixed $data    data
     * @param bool $return should return json
     * @return string          json encoded string
     */
    public static function print_status($status = 200, $data = [], $options = JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK, $return = false) {
        if (is_numeric($status)) $status_code = $status;
        else if (is_bool($status)) $status_code = $status ? 200 : 400;
        else $status_code = strtolower($status) === 'ok' ? 200 : 400;

        $status_name = self::http_code($status_code);
        self::set_status($status_code);

        if (!is_array($data) && !$data) {
            $data = ['message' => $status_name];
        } else if (is_string($data)) {
            $data = ['message' => $data];
        }

        if ($status_code >= 400 || $status_code < 200) $data['error'] = $status_name;

        $json = json_encode($data, $options);
        if ($return) return $json;
        else echo $json;
    }
    /**
     * Check params of an array/object provided by the given required keys
     * @param  mixed $required array or object that are required
     * @param  mixed $fields  array or object that contains the currrent provided params
     * @return boolean         true if validated, otherwise false
     */
    public static function verify_fields($required, $fields = null, &$missing = []) {
        if (!$fields) {
            $missing = $required;
            return false;
        }

        foreach ($required as $field) {
            $isset = is_array($fields) ? isset($fields[$field]) : isset($fields->{$field});
            if (!$isset) $missing[] = $field;
        }

        return $missing ? false : true;
    }

    public static function is_ajax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * check if the running script is in CLI mode
     * @return boolean [description]
     */
    public static function is_cli() {
        return php_sapi_name() == 'cli' || !isset($_SERVER["REQUEST_METHOD"]);
    }
    /**
     * Convert a string to friendly SEO string
     * @param  string $text input
     * @return string       output
     */
    public static function slugify($text, $lowercase = true, $skip_chars = '') {

        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d'.$skip_chars.']+~u', '-', $text);
        // trim
        $text = trim($text, '-');
        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // lowercase
        if ($lowercase) $text = strtolower($text);
        // remove unwanted characters
        $text = preg_replace('~[^-\w'.$skip_chars.']+~', '', $text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
    /**
     * Set values from default properties of an array
     * @param array $defaults  The defualt array structure
     * @param array $values        The input array
     * @param string $default_key Default key if input is a string or something
     * @return array                    Returns the right array
     */
    public static function set_values($defaults, $values, $default_key = "") {
        if ($default_key != "") {
            if (!is_array($values)) {
                if (isset($defaults[$default_key])) $defaults[$default_key] = $values;
                return $defaults;
            }
        }

        if ($values) {
            foreach ($values as $key => $value) {
                if (array_key_exists($key, $defaults)) $defaults[$key] = $value;
            }
        }

        return $defaults;
    }
    /**
     * Read CSV from URL or File
     * @param  string $filename  Filename
     * @param  string $headers Delimiter
     * @return array            [description]
     */
    public static function read_csv($filename, $with_header = true, $headers = null, $delimiter = ',') {
        $data = array();
        $index = 0;
        $header_count = $headers ? count($headers) : 0;

        $handle = @fopen($filename, "r") or false;
        if ($handle !== FALSE) {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                if ($index == 0 && $with_header) {
                    if (!$headers) $headers = $row;
                    $header_count = count($headers);
                } else {
                    if ($headers) {
                        $column_count = count($row);
                        if ($header_count > $column_count) {
                            $row = array_merge($row, array_fill_keys(range($column_count, $header_count - 1), null));
                        } else if ($header_count < $column_count) {
                            $extracted = array_splice($row, $header_count);
                            $row[$header_count - 1] = $row[$header_count - 1].'|'.implode('|', $extracted);
                            trigger_error('read_csv: row '.$index.' column mismatch. headers: '.$header_count.', columns: '.$column_count);
                        }

                        $data[] = array_combine($headers, $row);
                    } else {
                        $data[] = $row;
                    }
                }

                $index++;
            }

            fclose($handle);
        }

        return $data;
    }
    /**
     * Parse email address string
     * @param  string $str       string input
     * @param  string $separator separator, default ","
     * @return array             array
     */
    public static function parse_email($str, $separator = ",") {

        $str = trim(preg_replace('/\s+/', ' ', $str));
        $all = [];
        $emails = preg_split('/(".*?"\s*<.+?>)\s*' . $separator . '*|' . $separator . '+/', $str, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($emails as $email) {
            $name = "";
            $email = trim($email);
            $email_info = new stdClass;
            if (preg_match('/(.*?)<(.*)>/', $email, $regs)) {
                $email_info->name = trim(trim($regs[1]) , '"');
                $email_info->email = trim($regs[2]);
            } else {
                $email_info->name = $email;
                $email_info->email = $email;
            }

            if (strpos($email_info->email, $separator) !== false) {
                $addtl_emails = parse_email($email_info->email, $separator);
                foreach ($addtl_emails as $addtl_email_info) {
                    if ($addtl_email_info->name == "" || $addtl_email_info->name == $addtl_email_info->email) $addtl_email_info->name = $email_info->name;

                    $all[] = $addtl_email_info;
                }
            } else {
                if (filter_var($email_info->email, FILTER_VALIDATE_EMAIL)) $all[] = $email_info;
            }
        }
        return $all;
    }
    /**
     * Store client session info to an object
     * @return stdClass returns the object containing details of the session
     */
    public static function get_session_info() {
        $browser_info = get_browser_info();
        $result = new stdClass;
        $result->ip = get_client_ip();
        $result->browser_info = (object)$browser_info;

        return $result;
    }

    public static function truncate($string, $limit, $break = " ", $pad = "&hellip;") {
        // return with no change if string is shorter than $limit
        if (strlen($string) <= $limit) return $string;
        // is $break present between $limit and the end of the string?
        if (false !== ($breakpoint = strpos($string, $break, $limit))) {
            if ($breakpoint < strlen($string) - 1) {
                $string = substr($string, 0, $breakpoint) . $pad;
            }
        }

        return $string;
    }
    /**
     * Get the client's IP Address
     * @return string IP address string
     */
    public static function get_client_ip() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP')) $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR')) $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED')) $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR')) $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED')) $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR')) $ipaddress = getenv('REMOTE_ADDR');
        else $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    /**
     * Get your browser's info
     * @return stdClass returns the object containing the info of your browser
     */
    public static function get_browser_info() {
        $u_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN';
        $bname = 'Unknown';
        $platform = 'Unknown';
        $ub = 'Unknown';
        $version = "";
        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }
        // Next get the name of the useragent yes seperately and for good reason
        if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif (preg_match('/Safari/i', $u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif (preg_match('/Opera/i', $u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        }
        // finally get the correct version number
        $known = [
            'Version',
            $ub,
            'other'
        ];
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue

        } else {
            // see how many we have
            $i = count($matches['browser']);
            if ($i != 1) {
                //we will have two since we are not using 'other' argument yet
                //see if version is before or after the name
                if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
                    $version = $matches['version'][0];
                } else {
                    $version = $matches['version'][1];
                }
            } else {
                $version = $matches['version'][0];
            }
        }
        // check if we have a number
        if ($version == null || $version == "") {
            $version = "?";
        }

        return [
            'user_agent' => $u_agent,
            'name' => $bname,
            'version' => $version,
            'platform' => $platform,
            'pattern' => $pattern
        ];
    }

    /**
     * Returns an base64 encoded encrypted string
     */
    public static function encrypt($data, $key, $iv) {
        $output = false;
        $encrypt_method = "AES-256-CBC";
        // hash
        $key = self::hash($key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr($iv, 0, 16);
        $output = openssl_encrypt($data, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);

        return $output;
    }

    /**
     * Returns decrypted original string
     */
    public static function decrypt($data, $key, $iv) {
        $output = false;
        $encrypt_method = "AES-256-CBC";
        // hash
        $key = self::hash($key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr($iv, 0, 16);
        $output = openssl_decrypt(base64_decode($data), $encrypt_method, $key, 0, $iv);

        return $output;
    }

    /* takes the input, scrubs bad characters */
    public static function parse_seo_string($input, $replace = '-', $remove_words_array = []) {
        //make it lowercase, remove punctuation, remove multiple/leading/ending spaces
        $return = trim(ereg_replace(' +', ' ', preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($input))));
        //remove words, if not helpful to seo
        //i like my defaults list in remove_words(), so I wont pass that array

        if ($remove_words_array) {
            //separate all words based on spaces
            $input_array = explode(' ', $return);
            //create the return array
            $result = [];
            //loops through words, remove bad words, keep good ones
            foreach ($input_array as $word) {
                //if it's a word we should add...
                if (!in_array($word, $remove_words_array) && ($unique_words ? !in_array($word, $return) : true)) {
                    $result[] = $word;
                }
            }
            //return good words separated by dashes
            $return = implode($replace, $result);
        }
        //convert the spaces to whatever the user wants
        //usually a dash or underscore..
        //...then return the value.
        return str_replace(' ', $replace, $return);
    }

    public static function set_status($status) {
        http_response_code($status);
    }

    public static function set_content_type($type = 'application/json') {
        header('Content-Type: ' . $type);
    }

    public static function encode_api_result($result, $format = "json") {
        switch ($format) {
            case "json":
                set_content_type("application/json");
                return json_encode($result);
            break;
            case "xml":
                set_content_type("text/xml");
                $xml = new XMLHelper("Response");
                return $xml->to_xml($result);
            break;
            default:
                return $result;
        }
    }

    public static function debug($var, $options = null, $return = false) {
        $is_cli = self::is_cli();
        $is_ajax = self::is_ajax();
        $is_pjax = self::is_pjax();

        $is_html = !($is_cli || $is_ajax) || $is_pjax;
        $dismiss = self::get('dismiss', $options, true);
        $escape = self::get('escape', $options, true);
        $new_line = self::get('newline', $options, true);

        $close_btn_html = $dismiss ? '
            <button type="button" class="close" aria-label="Close" onclick="this.parentNode.remove();">
                <span aria-hidden="true">&times;</span>
            </button>' : '';

        $info = print_r($var, true);

        $info = preg_replace('/\s+\(/', ' (', $info);
        $info = preg_replace('/ {4}([)])/', '$1', $info);

        $result = $is_html ? '
            <div class="debug">
                <pre>'.($escape ? self::escape_html($info) : $info).'</pre>
                '.$close_btn_html.'
            </div>' : $info.($new_line ? EOL : '');

        if ($return) return $result;
        else echo $result;
    }

    public static function uuid() {
        if (function_exists('com_create_guid') === true)
            return trim(com_create_guid(), '{}');

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function random_int($min, $max) {
        if (function_exists('random_int') === true)
            return random_int($min, $max);

        $range = $max - $min;
        if ($range < 1) return $min; // not so random...

        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1

        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd > $range);

        return $min + $rnd;
    }

    public static function token($length = 16) {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet); // edited

        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[self::random_int(0, $max-1)];
        }

        return $token;
    }

    public static function url_base64_decode($str) {
        return base64_decode(strtr($str, [
            '-' => '+',
            '_' => '=',
            '~' => '/'
        ]));
    }

    public static function url_base64_encode($str) {
        return strtr(base64_encode($str) , [
            '+' => '-',
            '=' => '_',
            '/' => '~'
        ]);
    }

    /**
     * redirect()
     *
     * @param mixed $location
     * @return
     */
    public static function redirect($location = null) {
        if (!is_null($location)) {
            header("Location: {$location}");
            exit;
        }
    }

    public static function format_address($data) {
        $addr = trim(self::br2nl(self::get('street_1', $data)));
        $addr1 = trim(self::br2nl(self::get('street_2', $data)));
        $city = trim(self::get('city', $data));
        $state = trim(self::get('state', $data));
        $zip = trim(self::get('zip', $data));
        $county = trim(self::get('county', $data));

        if (!$addr) return '';
        return $addr.($addr1 != '' ? ', '.$addr1 : '').', '.$city.', '.($county ? $county.', ' : '').$state.' '.$zip;
    }

    /**
     * format_datetime()
     *
     * @param mixed $format
     * @param mixed $dt
     * @return
     */
    public static function format_datetime($format, $dt = "") {
        $date = new DateTime($dt);
        return $date->format($format);
    }

    public static function isvalid_datetime($format, $dt) {
        $dt = DateTime::createFromFormat($format, $dt);
        return $dt !== false && !array_sum($dt->getLastErrors());
    }
    /**
     * get_timestamp()
     *
     * @param string $dt
     * @return
     */
    public static function get_timestamp($dt = "") {
        $date = new DateTime($dt);
        return $date->getTimestamp();
    }
    /**
     * escape_sql_string()
     *
     * @param mixed $value
     * @return
     */
    public static function escape_sql_string($value) {
        return addslashes($value);
    }
    /**
     * escape_sql_array()
     *
     * @param mixed $array
     * @return
     */
    public static function escape_sql_array($array) {
        foreach ($array as $key => $value) {
            $array[$key] = (is_string($value)) ? escape_sql_string($value) : $value;
        }
        return $array;
    }
    /**
     * time_in_words()
     *
     * @param mixed $timestamp
     * @return
     */
    public static function time_in_words($date, $with_time = true) {
        if (!$date) return 'N/A';
        $timestamp = get_timestamp($date);
        $distance = (round(abs(time() - $timestamp) / 60));

        if ($distance <= 1) {
            $return = ($distance == 0) ? 'a few seconds ago' : '1 minute ago';
        } elseif ($distance < 60) {
            $return = $distance . ' minutes ago';
        } elseif ($distance < 119) {
            $return = 'an hour ago';
        } elseif ($distance < 1440) {
            $return = round(floatval($distance) / 60.0) . ' hours ago';
        } elseif ($distance < 2880) {
            $return = 'Yesterday' . ($with_time ? ' at ' . date('g:i A', $timestamp) : '');
        } elseif ($distance < 14568) {
            $return = date('l, F d, Y', $timestamp) . ($with_time ? ' at ' . date('g:i A', $timestamp) : '');
        } else {
            $return = date('F d ', $timestamp) . ((date('Y') != date('Y', $timestamp) ? ' ' . date('Y', $timestamp) : '')) . ($with_time ? ' at ' . date('g:i A', $timestamp) : '');
        }

        return $return;
    }
    /**
     * escape_html()
     *
     * @param mixed $str_value
     * @return
     */
    public static function escape_html($src, $nl2br = false) {
        if (is_array($src)) {
            return array_map([__CLASS__, 'escape_html'], $src);
        } else if (is_object($src)) {
            return (object)array_map([__CLASS__, 'escape_html'] , self::to_array($src));
        } else {
            if (is_null($src)) $src = "";
            $new_str = is_string($src) ? htmlentities(html_entity_decode($src, ENT_QUOTES)) : $src;
            return $nl2br ? nl2br($new_str) : $new_str;
        }
    }

    public static function descape_html($src) {
        if (is_array($src)) {
            return array_map([__CLASS__, 'descape_html'], $src);
        } else if (is_object($src)) {
            return (object)array_map([__CLASS__, 'descape_html'], self::to_array($src));
        } else {
            if (is_null($src)) $src = "";
            $new_str = is_string($src) ? html_entity_decode($src, ENT_QUOTES) : $src;
            return $new_str;
        }
    }

    /*public static function br2empty($text) {
        return preg_replace('/<br\s*\/?>/i', '', $text);
    }*/

    public static function br2nl($text) {
        return preg_replace('/<br\s*\/?>/i', EOL, $text);
    }

    /**
     * Convert an object to an array
     * @param object  $object The object to convert
     * @reeturn array
     */
    public static function to_array($object) {
        if (is_array($object)) return $object;
        if (!is_object($object) && !is_array($object)) return $object;
        if (is_object($object)) $object = get_object_vars($object);

        return array_map([
            __CLASS__,
            'to_array'
        ], $object);
    }
    /**
     * Convert an array to an object
     * @param array  $array The array to convert
     * @reeturn object
     */
    public static function to_object($array, $recursive = false) {
        if (!is_object($array) && !is_array($array)) return $array;

        if (!$recursive) return (object)$array;

        if (is_array($array)) return (object)array_map([
            __CLASS__,
            'to_object'
        ], $array);
        else return $array;
    }
}

?>
