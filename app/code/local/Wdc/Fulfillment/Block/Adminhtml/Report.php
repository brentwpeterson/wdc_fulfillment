<?php

class Wdc_Fulfillment_Block_Adminhtml_Report extends Mage_Adminhtml_Block_Widget
{
	
	public function __construct()
	{
		parent::__construct();	
		//$this->setTemplate('sj/edit.phtml');
		$this->setTitle('Report Generator');
	}



	protected function _prepareLayout()
	{
		$this->setChild('save_button',
			$this->getLayout()->createBlock('adminhtml/widget_button')
			->setData(array(
						'label'     => Mage::helper('adminhtml')->__('Create Report'),
                      //  'onclick'   => "setLocation('".$this->getUrl('*/*/download', array('template_id' => 0))."')",
                        'onclick'   => 'submitform();',
                        'method' => 'post',
						'class' => 'save',
					))
				);

		return parent::_prepareLayout();
	}

	public function getSaveButtonHtml()
	{
		return $this->getChildHtml('save_button');
	}

	public function getSaveUrl()
	{
		return $this->getUrl('*/*/download', array('_current'=>true));
	}
	

	public function initForm()
	{		
		$this->setChild('form',	$this->getLayout()->createBlock('fulfillment/adminhtml_form')->initForm());
		
		return $this;
	}

    public function _toHtml(){
        $html = '

        <div class="content-header">
            <table cellspacing="0">
                <tr>
                    <td><h3>Report Generator</h3></td><td class="form-buttons">'. $this->getSaveButtonHtml() .'</td>
                 </tr>
            </table>
        </div><form action="'. $this->getSaveUrl() .'" method="post" id="config_edit_form" enctype="multipart/form-data">';

        $html.= $this->getBlockHtml('formkey') . '' .   $this->getChildHtml('form') . '</form>
        <script type="text/javascript">
        function submitform()
            {
            document.forms["config_edit_form"].submit();
            }
            </script>';
        return $html;
    }
}