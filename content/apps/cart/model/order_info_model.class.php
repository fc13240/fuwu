<?php
  
defined('IN_ECJIA') or exit('No permission resources.');

class order_info_model extends Component_Model_Model {
	public $table_name = '';
	public function __construct() {
		$this->table_name = 'order_info';
		parent::__construct();
	}
}

// end