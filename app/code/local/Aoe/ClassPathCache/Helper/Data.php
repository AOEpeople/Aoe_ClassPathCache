<?php

/**
 * Helper
 * 
 * @author Fabrizio Branca
 * @since 2013-05-23
 */
class Aoe_ClassPathCache_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Clear the class path cache
     *
     * @return bool
     */
    public function clearClassPathCache() {
        if (Varien_Autoload::isApcUsed() && php_sapi_name() == 'cli') {
            // do frontend call
            Mage::log('[ClassPathCache] Doing frontend call.');
            $response = file_get_contents($this->getUrl());
            if ($response != 'OK') {
                Mage::log('[ClassPathCache] Frontend call failed. Response: ' . $response);
                return FALSE;
            }
        }
        $this->revalidateCache();
        return TRUE;
    }

    /**
     * Revalidate all currently cached entries
     */
    public function revalidateCache()
    {
        $cache = Varien_Autoload::getCache();
        Varien_Autoload::setCache(array());
        foreach ($cache as $className => $path) {
            Varien_Autoload::getFullPath($className);
        }
    }

    /**
     * Check url
     *
     * @return bool
     */
    public function checkUrl()
    {
        $k = base64_decode(Mage::app()->getRequest()->getParam('k'));
        $v = base64_decode(Mage::app()->getRequest()->getParam('v'));
        $ek = Mage::helper('core')->decrypt($v);
        return $k && $v && ($ek == $k);
    }

    /**
     * Check url
     *
     * @return bool
     */
    public function getUrl()
    {
        $k = Mage::helper('core')->getRandomString(16);
        return Mage::getUrl('aoeclasspathcache/index/clear', array(
            'k' => base64_encode($k),
            'v' => base64_encode(Mage::helper('core')->encrypt($k)),
            '_store' => 'default' // TODO: that's not nice
        ));
    }


}
