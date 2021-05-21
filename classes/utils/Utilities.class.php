<?php

/*
 * FileSender www.filesender.org
 *
 * Copyright (c) 2009-2012, AARNet, Belnet, HEAnet, SURFnet, UNINETT
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * *    Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 * *    Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in the
 *     documentation and/or other materials provided with the distribution.
 * *    Neither the name of AARNet, Belnet, HEAnet, SURFnet and UNINETT nor the
 *     names of its contributors may be used to endorse or promote products
 *     derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

// Require environment (fatal)
if (!defined('FILESENDER_BASE')) {
    die('Missing environment');
}

require_once(FILESENDER_BASE.'/lib/random_compat/lib/random.php');

/**
 * Utility functions holder
 */
class Utilities
{
    /**
     * CSRF token
     */
    const SECURITY_TOKEN_LIFETIME = 7200; // seconds
    const OLD_SECURITY_TOKEN_LIFETIME = 900; // seconds
    private static $security_token = null;
    
    /**
     * Generate a unique ID to be used as token
     *
     * @param callable $unicity_checker callback used to check for uid unicity (takes uid as sole argument, returns bool telling if uid is unique), null if check not needed
     * @param int $max_tries maximum number of tries before giving up and throwing
     *
     * @return string uid
     *
     * @throws UtilitiesUidGeneratorBadUnicityCheckerException
     * @throws UtilitiesUidGeneratorTriedTooMuchException
     */
    public static function generateUID($unicity_checker = null, $max_tries = 1000)
    {
        // Do we need to generate a unicity-checked random UID ?
        if ($unicity_checker) {
            // Fail if checker is not a callable
            if (!is_callable($unicity_checker)) {
                throw new UtilitiesUidGeneratorBadUnicityCheckerException();
            }
            
            // Try to generate until uniquely-checked or max tries reached
            $tries = 0;
            do {
                $uid = self::generateUID();
                $tries++;
            } while (!call_user_func($unicity_checker, $uid, $tries) && ($tries <= $max_tries));
            
            // Fail if max tries reached
            if ($tries > $max_tries) {
                throw new UtilitiesUidGeneratorTriedTooMuchException($tries);
            }
            
            return $uid;
        }
        
        // Generate 16 bytes of random data (128 bits)
        $bytes = random_bytes(16);
        // Set bits required for a valid UUIDv4
        $bytes{8} = chr((ord($bytes{8}) & 0x3F) | 0x80); // Eat 2 bits of entropy
        $bytes{6} = chr((ord($bytes{6}) & 0x4F) | 0x40); // Eat 4 bits of entropy
        // $bytes has now 122 bits of entropy

        // Convert bytes to hex and split in 4-char strings (hex, so 2 bytes per string)
        $parts = str_split(bin2hex($bytes), 4);
        // Add dashes where UUIDs should have dashes
        return implode('-', array(
                $parts[0] . $parts[1],
                $parts[2],
                $parts[3],
                $parts[4],
                $parts[5] . $parts[6] . $parts[7]
        ));

    }

    /**
     * Generate a string contains numbytes of entropy and is then
     * encoded as a hex string for storage in a database or transmission.
     *
     * @param $numbytes int number of bytes with forced min value of 16.
     */
    public static function generateEntropyString( $numbytes = 16 )
    {
        if( is_null($numbytes) || $numbytes < 16 ) {
            $numbytes = 16;
        }
        $bytes = random_bytes($numbytes);
        $ret = bin2hex($bytes);
        return $ret;
    }
    
    /**
     * Validates a personal message
     *
     */
    public static function isValidMessage($msg)
    {
        $r = Config::get('message_can_not_contain_urls_regex');
        if (strlen($r) && preg_match('/' . $r . '/', $msg)) {
            return false;
        }
        return true;
    }
    

    /**
     * Validates unique ID format
     *
     * @param string $uid
     *
     * @return bool
     */
    public static function isValidUID($uid)
    {
        return preg_match('/^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/i', $uid);
    }

    public static function validateEmail($email)
    {
        $ret = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!$ret) {
            return false;
        }
        if (preg_match('/"@/', $email)) {
            return false;
        }
        if (preg_match('/\\\\/', $email)) {
            return false;
        }
        return $ret;
    }
    
    /**
     * Validates a filename
     *
     * @param string $filename
     *
     * @return bool
     */
    public static function isValidFileName($filename)
    {
        return preg_match('/' .  Config::get('valid_filename_regex') . '$/u', $filename);
    }

    
    
    /**
     * Format a date according to configuration
     *
     * @param integer $timestamp php timestamp to format to date or null to use current date
     * @param bool $with_time
     *
     * @return string utf8 encoded formatted date
     */
    public static function formatDate($timestamp = null, $with_time = false)
    {
        if (is_null($timestamp)) {
            $timestamp = time();
        }
        
        if (!$timestamp) {
            return '';
        }

        Lang::setlocale_fromUserLang( LC_TIME );

        $lid = $with_time ? 'datetime_format' : 'date_format';
        $dateFormat = Lang::trWithConfigOverride($lid);
        if ($dateFormat == '{date_format}') {
            $dateFormat = '%d/%m/%Y';
        }
        if ($dateFormat == '{datetime_format}') {
            $dateFormat = '%d/%m/%Y %T';
        }

        return utf8_encode(strftime($dateFormat, $timestamp));
    }
    
    /**
     * Format a time according to configuration
     *
     * @param integer $time in seconds
     *
     * @return string formatted time
     */
    public static function formatTime($time)
    {
        if (!$time) {
            return '0s';
        }
        
        // Get time format
        $time_format = Lang::tr('time_format');
        if ($time_format == '{time_format}') {
            $time_format = '{h:H\h} {i:i\m\i\n} {s:s\s}';
        }
        
        // convert time to time parts
        $bits = array();
        $bits['h'] = floor($time / 3600);
        $time %= 3600;
        $bits['i'] = floor($time / 60);
        $bits['s'] = $time % 60;
        
        // Process and replace bits in format string
        foreach ($bits as $k => $v) {
            if ($v) {
                $time_format = preg_replace_callback('`\{'.$k.':([^}]+)\}`', function ($m) use ($k, $v) {
                    return preg_replace_callback('`(?<!\\\\)'.$k.'`i', function ($m) use ($v) {
                        return sprintf('%02d', $v);
                    }, $m[1]);
                }, $time_format);
            } else { // Remove part if zero
                $time_format = preg_replace('`\{'.$k.':[^}]+\}`', '', $time_format);
            }
        }
        
        // Remove backslashes
        $time_format = str_replace('\\', '', $time_format);
        
        // Strip leading 0s
        $time_format = preg_replace('`^[\s0]+`', '', trim($time_format));
        
        if (!$time_format) {
            $time_format = '0s';
        }
        
        return $time_format;
    }
    
    /**
     * Turn PHP defined size (ini files) to bytes
     *
     * @param string $size the size to analyse
     *
     * @return integer the size in bytes
     */
    public static function sizeToBytes($size)
    {
        // Check format
        if (!preg_match('`^([0-9]+)([ptgmk])?$`i', trim($size), $parts)) {
            throw new BadSizeFormatException($size);
        }
        
        $size = (int)$parts[1];
        
        if (count($parts) > 2) {
            switch (strtoupper($parts[2])) {
            case 'P': $size *= 1024;
            // no break
            case 'T': $size *= 1024;
            // no break
            case 'G': $size *= 1024;
            // no break
            case 'M': $size *= 1024;
            // no break
            case 'K': $size *= 1024;
        }
        }
        return $size;
    }
    
    /**
     * Format size
     *
     * @param int $bytes
     *
     * @return string
     */
    public static function formatBytes($bytes, $precision = 1)
    {
        // Default
        if (!$precision || !is_numeric($precision)) {
            $precision = 2;
        }
        // allow sloppy $bytes
        $bytes = floor($bytes);
        
        // Variants
        $unit = Lang::tr('size_unit')->out();
        if ($unit == '{size_unit}') {
            $unit = 'b';
        }
        
        $multipliers = array('', 'k', 'M', 'G', 'T');
        
        // Compute multiplier
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($multipliers) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision).' '.$multipliers[$pow].$unit;
    }
    
    /**
     * Get remote client IP (v4 or v6)
     *
     * @return string
     */
    public static function getClientIP()
    {
        $ips = array();
        
        $candidates = array_reverse((array)Config::get('client_ip_key'));
        foreach($candidates as $candidate) {
            if(!array_key_exists($candidate, $_SERVER)) continue;
            
            foreach(explode(',', $_SERVER[$candidate]) as $value) {
                $ips[] = trim($value);
            }
        }
        
        $ips = array_filter($ips, function($ip) {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        });
        
        if(!count($ips)) {
            if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
                return $_SERVER['REMOTE_ADDR']; // fallback
            }
            return '127.0.0.1';
        }
        
        return array_pop($ips);
    }
    
    /**
     * Replace illegal chars with _ character in supplied file names
     *
     * @param string $filename
     *
     * @return string
     */
    public static function sanitizeFilename($filename)
    {
        //return preg_replace('`[^a-z0-9_\-\. ]`i', '_', $filename);
        return preg_replace('`^\.`', '_', (string)$filename);
    }
    
    /**
     * Sanitize input against encoding variations and (a bit) against html injection
     *
     * @param mixed $input
     *
     * @return mixed
     */
    public static function sanitizeInput($input)
    {
        if (is_array($input)) {
            foreach ($input as $k => $v) {
                $nk = preg_replace('`[^a-z0-9\._-]`i', '', $k);
                if ($k !== $nk) {
                    unset($input[$k]);
                }
                $input[$nk] = self::sanitizeInput($v);
            }
            
            return $input;
        }
        
        if (
            is_numeric($input)
            || is_bool($input)
            || is_null($input)
            || is_object($input) // How can that be ?
        ) {
            return $input;
        }
        
        if (is_string($input)) {
            // Convert to UTF-8
            $input = iconv(mb_detect_encoding($input, mb_detect_order(), true), 'UTF-8', $input);
            
            // Render potential tags useless by putting a space immediatelly after < which does not already have one
            $input = html_entity_decode($input, ENT_QUOTES, 'UTF-8');
            $input = preg_replace('`<([^\s])`', '< $1', $input);
            
            return $input;
        }
        
        // Still here ? Should not ...
        return null;
    }
    
    /**
     * Sanitize output
     *
     * @param string $output
     *
     * @return string
     */
    public static function sanitizeOutput($output)
    {
        return htmlentities($output, ENT_QUOTES, 'UTF-8');
    }

    public static function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public static function isTrue($v)
    {
        return $v == '1' || $v == 'true';
    }
    public static function boolToString($v)
    {
        if( $v == '1' || $v == 'true' ) {
            return 'true';
        }
        return 'false';
    }

    /**
     * This is a wrapper around the PHP http_build_query with some
     * smarts. $path is optional and if not given will be the site itself.
     * If path is given and is ^http then it is taken as the site url prefix.
     * On the other hand if path is relative then it is converted to absolute
     * by this call.
     *
     * CGI parameters are given in $q which can be an array like;
     * array( 'foo' => 'bar', 'baz' => 7 )
     *
     * @param array q the CGI parameters
     * @param string path either nothing (default), a relative prefox, or absolute url
     *
     * @return string full URL using path and query params using the user's specified
     *                path separator (; can be useful here).
     */
    public static function http_build_query($q, $path = null)
    {
        if ($path == null) {
            $path = Config::get('site_url') . '?';
        } else {
            if (!Utilities::startsWith($path, 'http')) {
                $path = Config::get('site_url') . $path;
            }
        }
        $ret = $path;
        $sep = ini_get('arg_separator.output');
        if (phpversion() < 5.4) {
            // CIFIXME remove this branch when CI php is upgraded.
            $ret .= http_build_query($q, '', $sep);
        } else {
            $ret .= http_build_query($q, '', $sep, PHP_QUERY_RFC3986);
        }
        return $ret;
    }
    
    /**
     * Check if HTTPS is in use
     *
     * @return bool
     */
    public static function httpsInUse()
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }
    
    /**
     * Get the security token, refreshing it in the process if needed
     *
     * @return string
     */
    public static function getSecurityToken()
    {
        if (!is_null(self::$security_token)) {
            return self::$security_token['value'];
        }
        
        // Fetch existing token
        $token = isset($_SESSION) && array_key_exists('security_token', $_SESSION) ? $_SESSION['security_token'] : null;
        
        // Old token style, cancel it
        if (!is_array($token)) {
            $token = null;
        }
        
        if (!$token) { // First access
            $token = array(
                'value' => Utilities::generateUID(),
                'valid_until' => time() + self::SECURITY_TOKEN_LIFETIME,
                'old' => null
            );
            Logger::debug('Generated security token, value is '.$token['value'].', valid until '.date('Y-m-d H:i:s', $token['valid_until']));
        } elseif ($token['valid_until'] < time()) { // Must renew
            Logger::debug('Security token expired, value was '.$token['value']);
            
            $token['old'] = array(
                'value' => $token['value'],
                'valid_until' => time() + self::OLD_SECURITY_TOKEN_LIFETIME
            );
            
            $token['value'] = Utilities::generateUID();
            $token['valid_until'] = time() + self::SECURITY_TOKEN_LIFETIME;
            
            Logger::debug('Generated new security token, value is '.$token['value'].', valid until '.date('Y-m-d H:i:s', $token['valid_until']));
        } else { // Still valid, scrape old value from any previous changes
            if ($token['old'] && $token['old']['valid_until'] < time()) {
                Logger::debug('Old security token expired, value was '.$token['old']['value']);
                $token['old'] = null;
            }
        }
        
        if ($token['old']) { // Send new value as header if changed
            header('X-Filesender-Security-Token: '.$token['value']);
        }
        
        // Store in session
        $_SESSION['security_token'] = $token;
        
        // Cache in class
        self::$security_token = $token;
        
        return $token['value'];
    }
    
    /**
     * Check given security token against stored one
     *
     * @param string $token_to_check
     *
     * @return bool
     */
    public static function checkSecurityToken($token_to_check)
    {
        $token = self::getSecurityToken();
        
        // Direct match
        if ($token_to_check === $token) {
            return true;
        }
        
        // If no direct match and no previous token value, no match
        if (!self::$security_token['old']) {
            return false;
        }
        
        // Previous value matches
        return $token_to_check === self::$security_token['old']['value'];
    }


    /**
     * Read the config $configkey and if it is set then regex
     * match it to see if needle matches and return the result.
     * This function handles empty configkey values and may cache results.
     *
     * So if you have a possible config key
     *    mykey_regex => 'foo.*',
     *
     * you can see if you match in code with
     * if( Utilities::configMatch( 'mykey_regex', 'bar' )) {
     *    ...
     * }
     */
    public static function configMatch($configkey, $needle)
    {
        $cfg = Config::get($configkey);
        if (!strlen($cfg)) {
            return false;
        }
        if (preg_match('/' . $cfg . '/', $needle)) {
            return true;
        }
        return false;
    }

    /**
     * Read a value from an array validating the result.
     * If the array doesn't have the key or validation fails
     * then return a default value.
     *
     * filtering is optional but highly recommended. If you want an int then
     * ask for one to be validated as such
     *
     * filter is from http://php.net/manual/en/filter.filters.validate.php
     */
    public static function arrayKeyOrDefault($array, $key, $def, $filter = FILTER_DEFAULT)
    {
        $r = $def;
        if (array_key_exists($key, $array)) {
            $t = $array[$key];
            if (isset($t)) {
                $r = $t;
            }
        }

        $options = array(
            'options' => array( 'default' => $def ),
        );
        $r = filter_var($r, $filter, $options);
        return $r;
    }

    /**
     * true if $v is array( array( ... ) )
     */
    public static function is_array_of_array($v)
    {
        if (!is_array($v)) {
            return false;
        }
        $sl = array_slice($v, 0, 1);
        if (is_array(array_shift($sl))) {
            return true;
        }
        return false;
    }

    public static function ensureArray($v) 
    {
        if(is_array($v))
            return $v;

        return array($v);
    }

    /**
     * This does some sniffing around first to see if the file exists
     * and can be read before trying to include it. If the include
     * fails then the haltmsg is logged and the function does not return.
     *
     * Note that at the moment no syntax checks are done on the included
     * file. If the file has errors in it then script execution will halt.
     *
     * @param string path the file to include_once()
     * @param haltmsg the message to halt with. This may be decorated with additional
     * info such as "file not found" if that specific error has occurred.
     */
    public static function include_once_or_halt($path, $haltmsg)
    {
        if (!file_exists($path)) {
            Logger::haltWithErorr('File not found at expected path ' . $path
                                 . ' ' . $haltmsg);
        }
        if (!is_readable($path)) {
            Logger::haltWithErorr('Can not read file at path ' . $path
                                . ' ' . $haltmsg);
        }

        // actually bring in the autoload file
        if ((include_once($path)) == false) {
            Logger::haltWithErorr('Failed to include file from path ' . $path
                                . ' ' . $haltmsg);
        }
    }

    /**
     * A central call to interact with the $_GET[] array
     * 
     * @param name name of CGI arg to get
     * @param def default value to return if name is not set in query.
     */
    public static function getGETparam( $name, $def = null ) 
    {
        $ret = $def;
        if(array_key_exists($name, $_GET)) {
            $ret = $_GET[$name];
        }
        return $ret;
    }
}
