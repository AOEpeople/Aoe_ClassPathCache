<?php
/**
 * Class path cache controller
 *
 * @author Fabrizio Branca
 * @since 2013-05-23
 */
class Aoe_ClassPathCache_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Clear class path cache
     *
     * @return void
     */
    public function clearAction()
    {
        if (Mage::helper('aoe_classpathcache')->checkUrl()) {
            if (Mage::helper('aoe_classpathcache')->clearClassPathCache()) {
                $this->getResponse()->setBody('OK');
            } else {
                $this->getResponse()->setBody('FAILED');
            }
        } else {
            $this->getResponse()->setBody('WRONG KEY');
        }
    }
}
