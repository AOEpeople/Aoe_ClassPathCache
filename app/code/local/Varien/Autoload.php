<?php

/**
 * Classes source autoload
 *
 * @author Fabrizio Branca
 */
class Varien_Autoload
{
    const SCOPE_FILE_PREFIX = '__';

    static protected $_instance;
    static protected $_scope = 'default';
    static protected $_cache = array();
    static protected $_numberOfFilesAddedToCache = 0;

    protected $_arrLoadedClasses    = array();

    /**
     * Class constructor
     */
    public function __construct()
    {
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
            return include BP . DS . $realPath;
        }
        return false;
    }

    static function getFileFromClassname($classname) {
        return str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $classname))) . '.php';
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
    static public function getCacheFilePath() {
        return BP . DS . 'var' . DS . 'classpathcache.php';
    }

    /**
     * Setting cache content
     *
     * @param array $cache
     */
    static public function setCache(array $cache) {
        self::$_cache = $cache;
    }

    /**
     * Load cache content from file
     *
     * @return array
     */
    static public function loadCacheContent() {
        if (file_exists(self::getCacheFilePath())) {
            include self::getCacheFilePath();
        }
    }

    /**
     * Get full path
     *
     * @param $classname
     * @return mixed
     */
    static public function getFullPath($classname) {
        if (!isset(self::$_cache[$classname])) {
            self::$_cache[$classname] = self::searchFullPath(self::getFileFromClassname($classname));
            // removing the basepath
            self::$_cache[$classname] = str_replace(BP . DS, '', self::$_cache[$classname]);
            self::$_numberOfFilesAddedToCache++;
        }
        return self::$_cache[$classname];
    }

    /**
     * Checks if a file exists in the include path and returns the full path if the file exists
     *
     * @param $filename
     * @return bool|string
     */
    static public function searchFullPath($filename)
    {
        $paths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $path) {
            $fullpath = $path . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($fullpath)) {
                return $fullpath;
            }
        }
        return false;
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if (self::$_numberOfFilesAddedToCache > 0) {
            ksort(self::$_cache);
            $fileContent = "<?php\n\n";
            $fileContent .= "// This file is automatically generated by Aoe_ClassPathCache. Don't change anything manually.\n";
            $fileContent .= "// Updated: ".date('Y-m-d H:i:s')."\n\n";
            $fileContent .= "Varien_Autoload::setCache(array(\n";
            foreach (self::$_cache as $classname => $filename) {
                $fileContent .= "    '$classname' => ";
                $fileContent .= !$filename ? 'false' : "'$filename'";
                $fileContent .= ",\n";
            }
            $fileContent .= "));";

            $tmpfile = tempnam(sys_get_temp_dir(), 'aoe_classpathcache');
            if (file_put_contents($tmpfile, $fileContent)) {
                if (rename($tmpfile, self::getCacheFilePath())) {
                    @chmod(self::getCacheFilePath(), 0664);
                }
            }

        }
    }

}
