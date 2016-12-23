<?php

/**
 * Classes source autoload
 *
 * @author Fabrizio Branca
 */
class Varien_Autoload
{
    const SCOPE_FILE_PREFIX = '__';
    const CACHE_KEY_PREFIX = 'classPathCache';

    protected static $_instance;
    protected static $_scope = 'default';
    protected static $_cache = array();
    protected static $_numberOfFilesAddedToCache = 0;

    public static $useAPC = null;
    protected static $cacheKey = self::CACHE_KEY_PREFIX;

    /* Base Path */
    protected static $_BP = '';

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

        // Allow APC to be disabled externally by explicitly setting Varien_Autoload::$useAPC = FALSE;
        if (self::$useAPC === null) {
            self::$useAPC = extension_loaded('apc') && ini_get('apc.enabled');
        }

        self::$cacheKey = self::CACHE_KEY_PREFIX . "_" . md5(self::$_BP);
        self::registerScope(self::$_scope);
        self::loadCacheContent();
    }

    /**
     * Singleton pattern implementation
     *
     * @return Varien_Autoload
     */
    public static function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new Varien_Autoload();
        }
        return self::$_instance;
    }

    /**
     * Register SPL autoload function
     */
    public static function register()
    {
        spl_autoload_register(array(self::instance(), 'autoload'));
    }

    /**
     * Load class source code
     *
     * @param string $class
     *
     * @return bool
     */
    public function autoload($class)
    {
        // Prevent fatal errors when PHP has already started shutting down
        if (!isset(self::$_cache)) {
            $classFile = str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $class)));
            return include $classFile . '.php';
        }

        // Get file path (from cache if available)
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
     *
     * @return string
     */
    public static function getFileFromClassName($className)
    {
        $className = ltrim($className, '\\');
        $fileName = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }

        $fileName .= str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $className))) . '.php';

        return $fileName;
    }

    /**
     * Register autoload scope
     * This process allow include scope file which can contain classes
     * definition which are used for this scope
     *
     * @param string $code scope code
     */
    public static function registerScope($code)
    {
        self::$_scope = $code;
    }

    /**
     * Get current autoload scope
     *
     * @return string
     */
    public static function getScope()
    {
        return self::$_scope;
    }

    /**
     * Get cache file path
     *
     * @return string
     */
    public static function getCacheFilePath()
    {
        return self::$_BP . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'classPathCache.php';
    }

    /**
     * Get revalidate flag file path
     *
     * @return string
     */
    public static function getRevalidateFlagPath()
    {
        return self::$_BP . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'classPathCache.flag';
    }

    /**
     * Setting cache content
     *
     * @param array $cache
     */
    public static function setCache(array $cache)
    {
        self::$_cache = $cache;
    }

    /**
     * Load cache content from file
     *
     * @return array
     */
    public static function loadCacheContent()
    {

        if (self::isApcUsed()) {
            $value = apc_fetch(self::getCacheKey());
            if ($value !== false) {
                self::setCache($value);
            }
        } elseif (file_exists(self::getCacheFilePath())) {
            $cacheFilePath = require self::getCacheFilePath();
            if (!is_array($cacheFilePath)) {
                $cacheFilePath = array();
            }

            self::setCache($cacheFilePath);
        }

        if (file_exists(self::getRevalidateFlagPath()) && unlink(self::getRevalidateFlagPath())) {
            // When this is called there might not be an autoloader in place. So we need to manually load all the needed classes:
            require_once implode(DIRECTORY_SEPARATOR, array(self::$_BP, 'app', 'code', 'core', 'Mage', 'Core', 'Helper', 'Abstract.php'));
            require_once implode(DIRECTORY_SEPARATOR, array(self::$_BP, 'app', 'code', 'community', 'Aoe', 'ClassPathCache', 'Helper', 'Data.php'));
            $helper = new Aoe_ClassPathCache_Helper_Data;
            $helper->revalidateCache();
        }

    }

    /**
     * Get full path
     *
     * @param $className
     *
     * @return mixed
     */
    public static function getFullPath($className)
    {
        if (!isset(self::$_cache[$className])) {
            self::$_cache[$className] = self::searchFullPath(self::getFileFromClassName($className));
            // removing the basepath
            if (self::$_cache[$className] !== false) {
                self::$_cache[$className] = str_replace(self::$_BP . DIRECTORY_SEPARATOR, '', self::$_cache[$className]);
            }
            self::$_numberOfFilesAddedToCache++;
        }
        return self::$_cache[$className];
    }

    /**
     * Checks if a file exists in the include path and returns the full path if the file exists
     *
     * @param $filename
     *
     * @return bool|string
     */
    public static function searchFullPath($filename)
    {
        // return stream_resolve_include_path($filename);
        $paths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $path) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        return false;
    }

    /**
     * Check if apc is used
     *
     * @return bool
     */
    public static function isApcUsed()
    {
        return self::$useAPC;
    }

    /**
     * Get cache key (for apc)
     *
     * @return string
     */
    public static function getCacheKey()
    {
        return self::$cacheKey;
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
     * Save the cache to disk
     */
    public function save()
    {
        if (self::isApcUsed()) {
            if (PHP_SAPI != 'cli') {
                apc_store(self::getCacheKey(), self::$_cache, 0);
            }
        } elseif (is_dir_writeable(dirname(self::getCacheFilePath()))) {
            $this->_createCacheFile();
        }
    }

    /**
     * Create the cache file
     */
    protected function _createCacheFile()
    {
        $content = "<?php\n// DO NOT EDIT THIS FILE!\n\nreturn array(\n";

        Mage::log(print_r(self::$_cache, true));
        foreach (self::$_cache as $key => $filename) {
            $content .= "\t'$key' => '$filename',\n";
        }

        $content .= ');';

        $this->_writeCacheFile($content);
    }

    /**
     * Write the cache to disk
     *
     * @param  string $content
     */
    protected function _writeCacheFile($content)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'aoe_classpathcache');
        if (file_put_contents($tmpFile, $content)) {
            if (@rename($tmpFile, self::getCacheFilePath())) {
                @chmod(self::getCacheFilePath(), 0664);
            } else {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if (self::$_numberOfFilesAddedToCache > 0) {
            $this->save();
        }
    }
}
