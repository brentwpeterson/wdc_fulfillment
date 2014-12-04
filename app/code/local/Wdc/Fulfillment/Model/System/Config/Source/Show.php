<?php

class Wdc_Fulfillment_Model_System_Config_Source_Show
{
	public function toOptionArray()
	{
		return array(
			array(
				'value' => 1,
					'label' => Mage::helper('fulfillment')->__('Yes')
			),
			array(
				'value' => 0,
					'label' => Mage::helper('fulfillment')->__('No')
			),
		);
	}

}
