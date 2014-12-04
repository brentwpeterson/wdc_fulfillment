<?php

class Wdc_Fulfillment_Block_Adminhtml_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function initForm()
    {
        $form = new Varien_Data_Form();

		$fieldset = $form->addFieldset('report_form', array('legend'=>Mage::helper('adminhtml')->__('Reports')));

      //  if (!Mage::app()->isSingleStoreMode()) {
        $fieldset->addField('store_id', 'select', array(
            'name'      => 'store_id',
            'label'     => Mage::helper('cms')->__('Store View'),
            'title'     => Mage::helper('cms')->__('Store View'),
            'required'  => true,
            'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
        ));
       // }


        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldset->addField('from_date', 'date', array(
            'name'   => 'from_date',
            'label'  => Mage::helper('adminhtml')->__('From Date'),
            'title'  => Mage::helper('adminhtml')->__('From Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'format'       => $dateFormatIso
        ));

        $fieldset->addField('to_date', 'date', array(
            'name'   => 'to_date',
            'label'  => Mage::helper('adminhtml')->__('To Date'),
            'title'  => Mage::helper('adminhtml')->__('To Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'format'       => $dateFormatIso
        ));

		$this->setForm($form);

		return $this;
	}
	
}

