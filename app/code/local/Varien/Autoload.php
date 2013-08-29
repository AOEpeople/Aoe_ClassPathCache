<?php

/**
 * Classes source autoload
 *
 * @author Fabrizio Branca
 * @author Dmytro Zavalkin <dimzav@gmail.com>
 */
class Varien_Autoload
{
    static protected $_instance;
    static protected $_scope = 'default';
    static protected $_cache;

    /* Base Path */
    static protected $_BP = '';

    /**
     * Class constructor
     */
    public function __construct()
    {
        if (defined('BP')) {
            self::$_BP = BP;
        } elseif (strpos($_SERVER["SCRIPT_FILENAME"], 'get.php') !== false) {
            global $bp; //get from get.php
            if (isset($bp) && !empty($bp)) {
                self::$_BP = $bp;
            }
        }

        self::registerScope(self::$_scope);
        self::loadCacheContent();
    }

    /**
     * Singleton pattern implementation
     *
     * @return Varien_Autoload
     */
    static public function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new Varien_Autoload();
        }

        return self::$_instance;
    }

    /**
     * Register SPL autoload function
     */
    static public function register()
    {
        spl_autoload_register(array(self::instance(), 'autoload'));
    }

    /**
     * Load class source code
     *
     * @param string $class
     * @return bool
     */
    public function autoload($class)
    {
        $realPath = self::getFullPath($class);
        if ($realPath !== false) {
            return include self::$_BP . DIRECTORY_SEPARATOR . $realPath;
        }

        return false;
    }

    /**
     * Get file name from class name
     *
     * @param string $className
     * @return string
     */
    static function getFileFromClassName($className)
    {
        return str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $className))) . '.php';
    }

    /**
     * Register autoload scope
     * This process allow include scope file which can contain classes
     * definition which are used for this scope
     *
     * @param string $code scope code
     */
    static public function registerScope($code)
    {
        self::$_scope = $code;
    }

    /**
     * Get current autoload scope
     *
     * @return string
     */
    static public function getScope()
    {
        return self::$_scope;
    }

    /**
     * Get cache file path
     *
     * @return string
     */
    static public function getCacheFilePath()
    {
        return self::$_BP . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR
        . 'classPathCache.php';
    }

    /**
     * Setting cache content
     *
     * @param array $cache
     */
    static public function setCache(array $cache)
    {
        self::$_cache = $cache;
    }

    /**
     * Load cache content from file
     *
     * @return array
     */
    static public function loadCacheContent()
    {
        if (file_exists(self::getCacheFilePath())) {
            $classPathCache = include self::getCacheFilePath();
            if (is_array($classPathCache)) {
                self::setCache($classPathCache);
            }
        }
    }

    /**
     * Get full path
     *
     * @param $className
     * @return mixed
     */
    static public function getFullPath($className)
    {
        if (!isset(self::$_cache[$className])) {
            self::$_cache[$className] = self::searchFullPath(self::getFileFromClassName($className));
            // removing the basepath
            self::$_cache[$className] = str_replace(self::$_BP . DIRECTORY_SEPARATOR, '', self::$_cache[$className]);
        }

        return self::$_cache[$className];
    }

    /**
     * Checks if a file exists in the include path and returns the full path if the file exists
     *
     * @param $filename
     * @return bool|string
     */
    static public function searchFullPath($filename)
    {
        return stream_resolve_include_path($filename);
    }

    /**
     * Get cache
     *
     * @return array
     */
    public static function getCache()
    {
        return self::$_cache;
    }

    /**
     * Get base path
     *
     * @return string
     */
    public static function getBp()
    {
        return self::$_BP;
    }
}
