<?php

class BlockMySales extends Module
{
	public function __construct()
	{
		$this->name = 'blockmysales';
		$this->tab = 'Blocks';
		$this->version = '1.0';

		parent::__construct();

		$this->displayName = $this->l('My modules/products block');
		$this->description = $this->l('Displays a block to submit/edit its own product');
	}

	public function install()
	{
		if (!$this->addMySalesBlockHook() OR !parent::install() OR !$this->registerHook('rightColumn'))
			return false;
		return true;
	}

	public function uninstall()
	{
		return parent::uninstall() AND $this->removeMySalesBlockHook();
	}

	public function hookLeftColumn($params)
	{
		return $this->hookRightColumn($params);
	}

	public function hookRightColumn($params)
	{
		global $smarty;
		
		if (!$params['cookie']->isLogged())
			return false;
			$smarty->assign(array(
				'HOOK_BLOCK_MY_SALES' => Module::hookExec('mySalesBlock')
		));
		return $this->display(__FILE__, $this->name.'.tpl');
	}

	private function addMySalesBlockHook()
	{
		return Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'hook` (`name`, `title`, `description`, `position`) VALUES (\'mySalesBlock\', \'My sales block\', \'Display extra informations inside the "my sales" block\', 1)');
	}

	private function removeMySalesBlockHook()
	{
		return Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'hook` WHERE `name` = \'mySalesBlock\'');
	}
}

?>
