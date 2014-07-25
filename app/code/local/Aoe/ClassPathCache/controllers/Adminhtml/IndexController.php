+<?php
/**
 * Class path cache controller for adminhtml
 *
 * @author Mathis Klooss
 * @since 2013-05-23
 */
class Aoe_ClassPathCache_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Clear classpathcache
     *
     * @return void
     */
    public function clearAction()
    {
        $success = true;
        $this->_helper()->revalidateCache();
        // check also frontend
        $response = file_get_contents($this->_helper()->getUrl());
        if ($response != 'OK') {
            $success = false;
        }
        // set message
        if($success)
        {
            $this->_session()->addSuccess($this->_helper()->__('the classpath cache was cleaned.'));
        } else {
            $this->_session()->addError($this->_helper()->__('could not clear classpath cache.'));
        }
        // redirect to referrer
        $this->_redirectReferer();
    }
    
    /**
     * class path helper
     * 
     * @return Aoe_ClassPathCache_Helper_Data
     */
    protected function _helper()
    {
        return Mage::helper('aoe_classpathcache');
    }

    /**
     * adminhtml session
     * 
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _session()
    {
        return Mage::getSingleton('adminhtml/session');
    }
}