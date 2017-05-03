<?php
  
defined('IN_ECJIA') or exit('No permission resources.');

class goods_cat_viewmodel extends Component_Model_View {
	public $table_name = '';
	public  $view = array();
	public function __construct() {
		$this->db_config  = RC_Config::load_config('database');
		$this->db_setting = 'default';
		$this->table_name = 'goods_cat';
		$this->table_alias_name	= 'gc';
		
		$this->view = array(
				'goods' => array(
						'type'  => Component_Model_View::TYPE_LEFT_JOIN,
						'alias' => 'g',
						'on'   	=> 'g.goods_id = gc.goods_id'
				)				
		);
		parent::__construct();
	}
}

// end