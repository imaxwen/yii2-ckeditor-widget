<?php

namespace CKSource\CKFinder;


class Utils
{
    /**
     * Convert shorthand php.ini notation into bytes, much like how the PHP source does it
     * @link http://pl.php.net/manual/en/function.ini-get.php
     *
     * @param string $val
     *
     * @return int
     */
    public static function returnBytes($val)
    {
        $val = strtolower(trim($val));

        if (!$val) {
            return 0;
        }

        $bytes = ltrim($val, '+');
        if (0 === strpos($bytes, '0x')) {
            $bytes = intval($bytes, 16);
        } elseif (0 === strpos($bytes, '0')) {
            $bytes = intval($bytes, 8);
        } else {
            $bytes = intval($bytes);
        }

        switch (substr($val, -1)) {
            case 't':
                $bytes *= 1024;
            case 'g':
                $bytes *= 1024;
            case 'm':
                $bytes *= 1024;
            case 'k':
                $bytes *= 1024;
        }

        return $bytes;
    }

    /**
     * The absolute pathname of the currently executing script.
     * If a script is executed with the CLI, as a relative path, such as file.php or ../file.php,
     * $_SERVER['SCRIPT_FILENAME'] will contain the relative path specified by the user.
     */
    public static function getRootPath()
    {
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            $sRealPath = dirname($_SERVER['SCRIPT_FILENAME']);
        } else {
            /**
             * realpath — Returns canonicalized absolute pathname
             */
            $sRealPath = realpath('.');
        }

        $sRealPath = static::trimPathTrailingSlashes($sRealPath);

        /**
         * The filename of the currently executing script, relative to the document root.
         * For instance, $_SERVER['PHP_SELF'] in a script at the address http://example.com/test.php/foo.bar
         * would be /test.php/foo.bar.
         */
        $sSelfPath = dirname($_SERVER['PHP_SELF']);
        $sSelfPath = static::trimPathTrailingSlashes($sSelfPath);

        return static::trimPathTrailingSlashes(substr($sRealPath, 0, strlen($sRealPath) - strlen($sSelfPath)));
    }

    /**
     * @param  string $path
     *
     * @return string
     */
    protected static function trimPathTrailingSlashes($path)
    {
        return rtrim($path, DIRECTORY_SEPARATOR . '/\\');
    }

    /**
     * Checks if array contains all specified keys
     *
     * @param array $array
     * @param array $keys
     *
     * @return true if array has all required keys, false otherwise
     */
    public static function arrayContainsKeys(array $array, array $keys)
    {
        return count(array_intersect_key(array_flip($keys), $array)) === count($keys);
    }

    /**
     * Simulate the encodeURIComponent() function available in JavaScript
     *
     * @param string $str
     *
     * @return string
     */
    public static function encodeURLComponent($str)
    {
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');

        return strtr(rawurlencode($str), $revert);
    }

    /**
     * Decodes URL component
     *
     * @param string $str
     *
     * @return string
     */
    public static function decodeURLComponent($str)
    {
        return rawurldecode($str);
    }

    /**
     * Decodes URL parts
     *
     * @param string $str
     *
     * @return string
     */
    public static function decodeURLParts($str)
    {
        return static::decodeURLComponent($str);
    }

    /**
     * Encodes URL parts
     *
     * @param string $str
     *
     * @return string
     */
    public static function encodeURLParts($str)
    {
        $revert = array('%2F'=>'/');

        return strtr(static::encodeURLComponent($str), $revert);
    }

    /**
     * Returns formatted date string generated for given timestamp
     *
     * @param int $timestamp
     *
     * @return string
     */
    public static function formatDate($timestamp)
    {
        return date('YmdHis', $timestamp);
    }
}

