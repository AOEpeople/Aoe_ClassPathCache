<?php

require_once 'abstract.php';

/**
 * AOE ClassPathCache CLI
 *
 * @author Fabrizio Branca
 * @since 2013-05-23
 */
class Aoe_ClassPathCache_Shell extends Mage_Shell_Abstract {

    /**
     * Clear
     */
    public function clearAction() {
        if (Mage::helper('aoe_classpathcache')->clearClassPathCache()) {
            echo "Success\n";
        } else {
            echo "Error while cleaning class path cache. Check system.log for details\n";
            exit(1);
        }
    }

    /**
     * Set revalidate flag
     */
    public function setRevalidateFlagAction() {
        $flagFile = Varien_Autoload::getRevalidateFlagPath();
        if (file_put_contents($flagFile, DATE_ISO8601)) {
            echo "Success\n";
        } else {
            echo "Error while writing '$flagFile'\n";
            exit(1);
        }
    }


    /** ****************************************************************************************************************
     * SHELL DISPATCHER
     **************************************************************************************************************** */

    /**
     * Run script
     *
     * @return void
     */
    public function run() {
        $action = $this->getArg('action');
        if (empty($action)) {
            echo $this->usageHelp();
        } else {
            $actionMethodName = $action.'Action';
            if (method_exists($this, $actionMethodName)) {
                $this->$actionMethodName();
            } else {
                echo "Action $action not found!\n";
                echo $this->usageHelp();
                exit(1);
            }
        }
    }



    /**
     * Retrieve Usage Help Message
     *
     * @return string
     */
    public function usageHelp() {
        $help = 'Available actions: ' . "\n";
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (substr($method, -6) == 'Action') {
                $help .= '    -action ' . substr($method, 0, -6);
                $helpMethod = $method.'Help';
                if (method_exists($this, $helpMethod)) {
                    $help .= $this->$helpMethod();
                }
                $help .= "\n";
            }
        }
        return $help;
    }


}

$shell = new Aoe_ClassPathCache_Shell();
$shell->run();

