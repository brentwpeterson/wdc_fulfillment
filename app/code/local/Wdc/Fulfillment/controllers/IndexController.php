<?php

class Wdc_Fulfillment_IndexController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {

//        echo 'seriously really fun<br>';
//
        echo Mage::getModel('fulfillment/inventory')->updateInventory();

//        echo '<h5>wheeeiieww!!!</h5>';
    }

	public function updateorderAction()
	{
		echo 'seriously really not fun<br>';
        echo Mage::getModel('fulfillment/inventory')->updateOrder();
		echo '<h5>wheeeiieww!!!</h5>';
		die;
	}

}
