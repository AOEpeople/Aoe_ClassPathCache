<?php
/**
 * @author Dmytro Zavalkin <dimzav@gmail.com>
 * @see https://github.com/magento/magento2/blob/master/dev/tools/classmap/fs_generator.php
 */

require_once 'abstract.php';

/**
 * AOE ClassPathCache generator.
 * Initial idea is from Magento 2 class map FS generator with additional improvements.
 */
class Aoe_ClassPathCache_Generator_Shell extends Mage_Shell_Abstract
{
    private static $fileTemplate = <<<'PHP'
<?php
class Aoe_ClassPathCache extends #CLASS#
{
    public function offsetGet($className)
    {
        return parent::offsetGet(#HASH_CLASS_NAME#);
    }
}

$classPathCache = new Aoe_ClassPathCache(#TYPE#);

PHP;

    /**
     * @var array
     */
    private static $cacheClassTypes = array(
        'ArrayObject_Int' => array(
            'class'  => 'ArrayObject',
            'type'   => '',
            'crc32'  => true
        ),
        'ArrayObject_String' => array(
            'class'  => 'ArrayObject',
            'type'   => '',
            'crc32'  => false
        ),
        'Judy_Int' => array(
            'class'  => 'Judy',
            'type'   => 'Judy::INT_TO_MIXED',
            'crc32'  => true
        ),
        'Judy_String' => array(
            'class'  => 'Judy',
            'type'   => 'Judy::STRING_TO_MIXED',
            'crc32'  => false
        ),
    );

    /**
     * Path to file where class path cache should be saved
     *
     * @var string
     */
    private $cacheFilePath;

    /**
     * Path to magento root folder
     *
     * @var string
     */
    private $basePath;

    /**
     * Initialize application and parse input parameters
     *
     * @param string $cacheFilePath
     * @param string $basePath
     */
    public function __construct($cacheFilePath = null, $basePath = null)
    {
        parent::__construct();

        $this->cacheFilePath = $cacheFilePath ?: Varien_Autoload::getCacheFilePath();
        $this->basePath      = $basePath ?: Varien_Autoload::getBp();

        if (!extension_loaded('judy')) {
            unset(self::$cacheClassTypes['Judy_Int']);
            unset(self::$cacheClassTypes['Judy_String']);
        }
    }

    /**
     * Run script
     *
     * @return void
     */
    public function run()
    {
        $action = $this->getArg('action');
        if (empty($action)) {
            echo $this->usageHelp();
        } else {
            $actionMethodName = $action . 'Action';
            if (method_exists($this, $actionMethodName)) {
                $this->$actionMethodName();
            } else {
                echo "Action {$action} not found!" . PHP_EOL;
                echo $this->usageHelp();
                exit(1);
            }
        }
    }

    /**
     * Retrieve usage help message
     *
     * @return string
     */
    public function usageHelp()
    {
        $help    = 'Available actions: ' . PHP_EOL;
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (substr($method, -6) == 'Action') {
                $help .= '    -action ' . substr($method, 0, -6);
                $helpMethod = $method . 'Help';
                if (method_exists($this, $helpMethod)) {
                    $help .= $this->$helpMethod();
                }
                $help .= PHP_EOL;
            }
        }

        return $help;
    }

    /**
     * Display generate action help
     *
     * @return string
     */
    public function generateActionHelp()
    {
        $types = implode(', ', array_keys(self::$cacheClassTypes));

        return " -type <type> Generate class path cache. Possible values of <type> argument: {$types}";
    }

    /**
     * Generate class path cache action
     */
    public function generateAction()
    {
        $type = $this->getArg('type');
        if (empty($type)) {
            echo "[-action generate] Please specify type with -type <type>." . PHP_EOL;
            echo $this->usageHelp();
            exit(1);
        }
        if (!isset(self::$cacheClassTypes[$type])) {
            echo "[-action generate -type {$type}] Unsupported type {$type}. Please specify correct one." . PHP_EOL;
            echo $this->usageHelp();
            exit(1);
        }

        echo "Class path cache generation started..." . PHP_EOL;

        $this->insertFileHeader($type);
        $this->insertFileBody($type);
        file_put_contents($this->cacheFilePath, 'return $classPathCache;' . PHP_EOL, FILE_APPEND);

        echo "Class path cache was successfully generated and saved to file "
            . realpath($this->cacheFilePath) . PHP_EOL;
    }

    /**
     * Insert file header with class declaration
     *
     * @param string $type
     */
    private function insertFileHeader($type)
    {
        $fileHeader = self::$fileTemplate;
        $hashClassNameMarkerReplacement = self::$cacheClassTypes[$type]['crc32'] ? 'crc32($className)' : '$className';
        $fileHeader = str_replace('#CLASS#', self::$cacheClassTypes[$type]['class'], $fileHeader);
        $fileHeader = str_replace('#HASH_CLASS_NAME#', $hashClassNameMarkerReplacement, $fileHeader);
        $fileHeader = str_replace('#TYPE#', self::$cacheClassTypes[$type]['type'], $fileHeader);

        file_put_contents($this->cacheFilePath, $fileHeader);
    }

    /**
     * Insert file body, i.e. many $classPathCache->offsetSet() lines
     *
     * @param string $type
     */
    private function insertFileBody($type)
    {
        $classMap             = $this->getClassMap();
        $errorMessage         = null;
        $crc32hashToClassName = array();
        foreach ($classMap as $className => $fileName) {
            if (self::$cacheClassTypes[$type]['crc32']) {
                $hash = crc32($className);
                if (isset($crc32hashToClassName[$hash])) {
                    $errorMessage = sprintf("crc32 hash collision between %s and %s classes."
                        . " Use any non-crc32 method to generate class path instead",
                        $crc32hashToClassName[$hash], $className
                    );
                    break;
                } else {
                    $crc32hashToClassName[$hash] = $className;
                    $line = sprintf('$classPathCache->offsetSet(%d, "%s");', $hash, $fileName);
                }
            } else {
                $line = sprintf('$classPathCache->offsetSet("%s", "%s");', $className, $fileName);
            }

            file_put_contents($this->cacheFilePath, $line . PHP_EOL, FILE_APPEND);
        }

        if ($errorMessage) {
            echo $errorMessage;
            unlink($this->cacheFilePath);
            exit(1);
        }
    }

    /**
     * Parse magento source code and build class map
     *
     * @return array
     */
    private function getClassMap()
    {
        $basePath  = realpath($this->basePath) . DIRECTORY_SEPARATOR;
        $directory = new RecursiveDirectoryIterator($basePath);
        $iterator  = new RecursiveIteratorIterator($directory);
        $regex     = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

        $map = array();
        foreach ($regex as $file) {
            $filePath = str_replace('\\', '/', str_replace($basePath, '', $file[0]));
            if (strpos($filePath, 'shell') === 0 || strpos($filePath, 'var') === 0
                || strpos($filePath, '.modman') === 0
            ) {
                continue;
            }

            $code   = file_get_contents($file[0]);
            $tokens = token_get_all($code);

            $count    = count($tokens);
            $i        = 0;
            while ($i < $count) {
                $token = $tokens[$i];

                if (!is_array($token)) {
                    $i++;
                    continue;
                }

                list($id) = $token;

                if ($id == T_CLASS || $id == T_INTERFACE) {
                    $class = null;
                    do {
                        $i++;
                        $token = $tokens[$i];
                        if (is_string($token)) {
                            continue;
                        }
                        list($type, $content) = $token;
                        if ($type == T_STRING) {
                            $class = $content;
                            break;
                        }
                    } while (empty($class) && $i < $count);

                    if (!empty($class)) {
                        $map[$class] = $filePath;
                    }
                }
                $i++;
            }
        }

        return $map;
    }
}

$shell = new Aoe_ClassPathCache_Generator_Shell();
$shell->run();
