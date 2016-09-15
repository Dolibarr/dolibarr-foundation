<?php
class AdminProductsController extends AdminProductsControllerCore
{
	public function __construct()
	{
		parent::__construct();

		$this->_select .= ', a.date_add as dateadd';

		$this->fields_list['dateadd'] = array(
				'title' => $this->l('Date add'),
				'align' => 'text-center',
				'class' => 'fixed-width-sm',
				'type' => 'date',
				'callback' => 'newProductAdd',
				'orderby' => false
		);

		$this->fields_list['dolibarr_core_include'] = array(
				'title' => $this->l('Request include core'),
				'align' => 'text-center',
				'active' => '',
				'class' => 'fixed-width-sm',
				'type' => 'bool',
				'orderby' => false
		);
	}

	public function processFilter()
	{
		parent::processFilter();
		$this->_filter = str_replace('`dateadd`', 'a.date_add', $this->_filter);
	}

	public function newProductAdd($dateadd, $product)
	{
		$now=time();
		$date2 = strtotime($dateadd);
		$date = $this->dateDiff($now, $date2);

		if ($product['active'] == 0 && $date['day'] < 30) // 1 mois
			return '<span style="color: #ff0000;">' . $this->l('New module to active') . '</span>';
		else
			return $dateadd;
	}

	public function dateDiff($date1, $date2)
	{
		$diff = abs($date1 - $date2); // abs pour avoir la valeur absolute, ainsi éviter d'avoir une différence négative
		$retour = array();

		$tmp = $diff;
		$retour['second'] = $tmp % 60;

		$tmp = floor( ($tmp - $retour['second']) /60 );
		$retour['minute'] = $tmp % 60;

		$tmp = floor( ($tmp - $retour['minute'])/60 );
		$retour['hour'] = $tmp % 24;

		$tmp = floor( ($tmp - $retour['hour'])  /24 );
		$retour['day'] = $tmp;

		return $retour;
	}
}