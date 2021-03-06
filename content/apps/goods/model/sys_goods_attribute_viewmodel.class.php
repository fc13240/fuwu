<?php
  
defined('IN_ECJIA') or exit('No permission resources.');

class sys_goods_attribute_viewmodel extends Component_Model_View {
	public $table_name = '';
	public  $view = array();
	public function __construct() {
		$this->table_name = 'goods_attr';
		$this->table_alias_name = 'ga';
		
		$this->view = array(
				'attribute' => array(
						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'a',
						'field' => 'ga.goods_attr_id, ga.attr_id, ga.attr_value, a.attr_name',
						'on'   	=> 'ga.attr_id = a.attr_id'
				)				
		);
		parent::__construct();
	}
}

// end