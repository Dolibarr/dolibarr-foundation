<?php

class blockmysalesadminajaxModuleFrontController extends ModuleFrontController
{
	public $json;
	public $status;
	public $confirmation=array();
	public $errors=array();

	/** @var array Confirmations displayed after post processing */
	protected $_conf;

	public function __construct()
	{
		parent::__construct();

		$this->_conf = array(
				1 => $this->module->l('Successful deletion'),
				2 => $this->module->l('The selection has been successfully deleted.'),
				3 => $this->module->l('Successful creation'),
				4 => $this->module->l('Successful update'),
				5 => $this->module->l('The status has been successfully updated.'),
				6 => $this->module->l('The settings have been successfully updated.'),
				7 => $this->module->l('The image was successfully deleted.'),
				8 => $this->module->l('The module was successfully downloaded.'),
				9 => $this->module->l('The thumbnails were successfully regenerated.'),
				10 => $this->module->l('The message was successfully sent to the customer.'),
				11 => $this->module->l('Comment successfully added'),
				12 => $this->module->l('Module(s) installed successfully.'),
				13 => $this->module->l('Module(s) uninstalled successfully.'),
				14 => $this->module->l('The translation was successfully copied.'),
				15 => $this->module->l('The translations have been successfully added.'),
				16 => $this->module->l('The module transplanted successfully to the hook.'),
				17 => $this->module->l('The module was successfully removed from the hook.'),
				18 => $this->module->l('Successful upload'),
				19 => $this->module->l('Duplication was completed successfully.'),
				20 => $this->module->l('The translation was added successfully, but the language has not been created.'),
				21 => $this->module->l('Module reset successfully.'),
				22 => $this->module->l('Module deleted successfully.'),
				23 => $this->module->l('Localization pack imported successfully.'),
				24 => $this->module->l('Localization pack imported successfully.'),
				25 => $this->module->l('The selected images have successfully been moved.'),
				26 => $this->module->l('Your cover image selection has been saved.'),
				27 => $this->module->l('The image\'s shop association has been modified.'),
				28 => $this->module->l('A zone has been assigned to the selection successfully.'),
				29 => $this->module->l('Successful upgrade'),
				30 => $this->module->l('A partial refund was successfully created.'),
				31 => $this->module->l('The discount was successfully generated.'),
				32 => $this->module->l('Successfully signed in to PrestaShop Addons')
		);
	}

	/**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $action='';
        $ajax=null;

        if (Tools::isSubmit('ajax')) $ajax = Tools::getValue('ajax');

        if ($ajax == 1)
        {
        	$context_customer_id = (int)$this->context->customer->id;
        	$customer_id = $context_customer_id;

        	if (!empty($customer_id))
        	{
        		$customer = BlockMySales::getCustomer($this->context->customer, $customer_id);

        		if ($customer !== false)
        		{
        			if (!is_null($customer) && !empty($customer))
        			{
        				if (Tools::isSubmit('action'))	$action = Tools::getValue('action');

        				if ($action == 'updateImagePosition')
        				{
        					$res = false;
        					if ($json = Tools::getValue('json'))
        					{
        						$res = true;
        						$json = stripslashes($json);
        						$images = Tools::jsonDecode($json, true);
        						foreach ($images as $id => $position)
        						{
        							$img = new Image((int)$id);
        							$img->position = (int)$position;
        							$res &= $img->update();
        						}
        					}
        					if ($res)
        						$this->jsonConfirmation($this->_conf[25]);
        					else
        						$this->jsonError(Tools::displayError('An error occurred while attempting to move this picture.'));

        					echo json_encode(array('confirmations' => $this->confirmations, 'error' => $this->errors));
        				}
        				else if ($action == 'updateCover')
        				{
        					Image::deleteCover((int)Tools::getValue('id_product'));
        					$img = new Image((int)Tools::getValue('id_image'));
        					$img->cover = 1;

        					@unlink(_PS_TMP_IMG_DIR_.'product_'.(int)$img->id_product.'.jpg');
        					@unlink(_PS_TMP_IMG_DIR_.'product_mini_'.(int)$img->id_product.'_'.$this->context->shop->id.'.jpg');

        					if ($img->update())
        						$this->jsonConfirmation($this->_conf[26]);
        					else
        						$this->jsonError(Tools::displayError('An error occurred while attempting to move this picture.'));

        					echo json_encode(array('confirmations' => $this->confirmations, 'error' => $this->errors));
        				}
        			}
        		}
        	}
        }
    }

    /**
     * Shortcut to set up a json success payload
     *
     * @param $message success message
     */
    public function jsonConfirmation($message)
    {
    	$this->json = true;
    	$this->confirmations[] = $message;
    	if ($this->status === '')
    		$this->status = 'ok';
    }

    /**
     * Shortcut to set up a json error payload
     *
     * @param $message error message
     */
    public function jsonError($message)
    {
    	$this->json = true;
    	$this->errors[] = $message;
    	if ($this->status === '')
    		$this->status = 'error';
    }
}
