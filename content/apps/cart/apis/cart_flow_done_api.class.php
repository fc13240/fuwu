<?php
  
use Ecjia\System\Notifications\OrderPlaced;
defined('IN_ECJIA') or exit('No permission resources.');


/**
 * @author will.chen
 */
class cart_flow_done_api extends Component_Event_Api {

    /**
     * @param
     *
     * @return array
     */
	public function call(&$options) {

		RC_Loader::load_app_class('cart', 'cart', false);

		$order = $options['order'];

		/* 获取用户收货地址*/
		if ($options['address_id'] == 0) {
			$consignee = cart::get_consignee($_SESSION['user_id']);
		} else {
			$consignee = RC_DB::table('user_address')
			->where('address_id', $options['address_id'])
			->where('user_id', $_SESSION['user_id'])
			->first();
		}
		if (isset($consignee['latitude']) && isset($consignee['longitude'])) {
			$geohash = RC_Loader::load_app_class('geohash', 'store');
			$geohash_code = $geohash->encode($consignee['latitude'] , $consignee['longitude']);
			$geohash_code = substr($geohash_code, 0, 5);
			$store_id_group = RC_Api::api('store', 'neighbors_store_id', array('geohash' => $geohash_code));
		} else {
			return new ecjia_error('pls_fill_in_consinee_info', '请完善收货人信息！');
		}

		/* 检查购物车中是否有商品 */
		$get_cart_goods = RC_Api::api('cart', 'cart_list', array('cart_id' => $options['cart_id'], 'flow_type' => $options['flow_type'], 'store_group' => $store_id_group));

		if (is_ecjia_error($get_cart_goods)) {
		    return $get_cart_goods;
		}
		if (count($get_cart_goods['goods_list']) == 0) {
			return new ecjia_error('not_found_cart_goods', '购物车中没有您选择的商品');
		}

		$cart_goods = $get_cart_goods['goods_list'];

		/* 判断是不是实体商品  及店铺数量如有多家店铺返回错误*/
		$store_group = array();
		foreach ($cart_goods as $val) {
			$store_group[] = $val['store_id'];
			/* 统计实体商品的个数 */
			if ($val['is_real']) {
				$is_real_good = 1;
			}
		}
		$store_group = array_unique($store_group);
		if (count($store_group) > 1) {
			return new ecjia_error('pls_single_shop_for_settlement', '请单个店铺进行结算!');
		}
		$order['store_id'] = $store_group[0];

		/* 检查收货人信息是否完整 */
		if (!cart::check_consignee_info($consignee, $options['flow_type'])) {
			/* 如果不完整则转向到收货人信息填写界面 */
			return new ecjia_error('pls_fill_in_consinee_info', '请完善收货人信息！');
		}

		/* 检查商品库存 */
		/* 如果使用库存，且下订单时减库存，则减少库存 */
		if (ecjia::config('use_storage') == '1' && ecjia::config('stock_dec_time') == SDT_PLACE) {
			$cart_goods_stock = $get_cart_goods['goods_list'];
			$_cart_goods_stock = array();
			foreach ($cart_goods_stock['goods_list'] as $value) {
				$_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
			}
			$result = cart::flow_cart_stock($_cart_goods_stock);
			if (is_ecjia_error($result)) {
				return $result;
			}
			unset($cart_goods_stock, $_cart_goods_stock);
		}

		/* 扩展信息 */
		if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) != CART_GENERAL_GOODS) {
			$order['extension_code']	= $_SESSION['extension_code'];
			$order['extension_id']		= $_SESSION['extension_id'];
		} else {
			$order['extension_code'] = '';
			$order['extension_id']   = 0;
		}

		/* 检查积分余额是否合法 */
		$user_id = $_SESSION['user_id'];
		if ($user_id > 0) {
			$user_info = RC_Api::api('user', 'user_info', array('user_id' => $user_id));
			// 查询用户有多少积分
			$flow_points = cart::flow_available_points($options['cart_id']); // 该订单允许使用的积分
			$user_points = $user_info['pay_points']; // 用户的积分总数

			$order['integral'] = min($order['integral'], $user_points, $flow_points);
			if ($order['integral'] < 0) {
				$order['integral'] = 0;
			}
		} else {
			$order['surplus']  = 0;
			$order['integral'] = 0;
		}

		/* 检查红包是否存在 */
		if ($order['bonus_id'] > 0) {
			$bonus = RC_Api::api('bonus', 'bonus_info', array('bonus_id' => $order['bonus_id']));
			if (empty($bonus) || ($bonus['store_id'] != 0 && $bonus['store_id'] != $order['store_id']) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart::cart_amount(true, $options['flow_type'])) {
				$order['bonus_id'] = 0;
			}
		} elseif (isset($options['bonus_sn'])) {
			$bonus_sn = trim($options['bonus_sn']);
			$bonus = RC_Api::api('bonus', 'bonus_info', array('bonus_id' => 0, 'bonus_sn' => $bonus_sn));
			$now = RC_Time::gmtime();
			if (empty($bonus) || $bonus['store_id'] != $order['store_id'] || $bonus['user_id'] > 0 || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart::cart_amount(true, $options['flow_type']) || $now > $bonus['use_end_date']) {} else {
				if ($user_id > 0) {
					$db_user_bonus = RC_DB::table('user_bonus');
					$db_user_bonus->where('bonus_id', $bonus['bonus_id'])->update(array('user_id' => $user_id));
				}
				$order['bonus_id'] = $bonus['bonus_id'];
				$order['bonus_sn'] = $bonus_sn;
			}
		}

		/* 检查商品总额是否达到最低限购金额 */
		if ($options['flow_type'] == CART_GENERAL_GOODS && cart::cart_amount(true, CART_GENERAL_GOODS, $options['cart_id']) < ecjia::config('min_goods_amount')) {
			return new ecjia_error('bug_error', '您的商品金额未达到最低限购金额！');
		}

		/* 收货人信息 */
		foreach ($consignee as $key => $value) {
			$order[$key] = addslashes($value);
			if($key == 'address_info'){
				$order['address'] = $order['address'].$order[$key];
			}
		}
	
		if (isset($is_real_good)) {
			$shipping_method = RC_Loader::load_app_class('shipping_method', 'shipping');
			$data = $shipping_method->shipping_info($order['shipping_id']);
			if (empty($data['shipping_id'])) {
				return new ecjia_error('shipping_error', '请选择一个配送方式！');
			}
		}

		/* 订单中的总额 */
		$total = cart::order_fee($order, $cart_goods, $consignee, $options['cart_id']);
		$order['bonus']			= $total['bonus']; 
		$order['goods_amount']	= $total['goods_price'];
		$order['discount']		= empty($total['discount']) ? 0.00 : $total['discount'];
		$order['surplus']		= $total['surplus'];
		$order['tax']			= $total['tax'];

		// 购物车中的商品能享受红包支付的总额
		$discount_amout = cart::compute_discount_amount($options['cart_id']);
		// 红包和积分最多能支付的金额为商品总额
		$temp_amout = $order['goods_amount'] - $discount_amout;
		if ($temp_amout <= 0) {
			$order['bonus_id'] = 0;
		}

		/* 配送方式 */
		if ($order['shipping_id'] > 0) {
			$shipping_method = RC_Loader::load_app_class('shipping_method', 'shipping');
			$shipping = $shipping_method->shipping_info($order['shipping_id']);
			$order['shipping_name'] = addslashes($shipping['shipping_name']);
		}
		$order['shipping_fee'] = $total['shipping_fee'];
		$order['insure_fee'] = $total['shipping_insure'];

		$payment_method = RC_Loader::load_app_class('payment_method','payment');
		/* 支付方式 */
		if ($order['pay_id'] > 0) {
			$payment = $payment_method->payment_info_by_id($order['pay_id']);
			$order['pay_name'] = addslashes($payment['pay_name']);
			//如果是货到付款，状态设置为已确认。
			if($payment['pay_code'] == 'pay_cod') {
				$order['order_status'] = 1;
				$store_info = RC_DB::table('store_franchisee')->where('store_id', $store_group[0])->first();
				/* 货到付款判断是否是自营*/
				if ($store_info['manage_mode'] != 'self') {
					return new ecjia_error('pay_not_support', '货到付款不支持非自营商家！');
				}
			}

		}
		$order['pay_fee']	= $total['pay_fee'];
		$order['cod_fee']	= $total['cod_fee'];

		$order['pack_fee']	= $total['pack_fee'];
		$order['card_fee']	= $total['card_fee'];

		$order['order_amount'] = number_format($total['amount'], 2, '.', '');

		/* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
		if ($order['order_amount'] <= 0) {
			$order['order_status']	= OS_CONFIRMED;
			$order['confirm_time']	= RC_Time::gmtime();
			$order['pay_status']	= PS_PAYED;
			$order['pay_time']		= RC_Time::gmtime();
			$order['order_amount']	= 0;
		}

		$order['integral_money']	= $total['integral_money'];
		$order['integral']			= $total['integral'];

		if ($order['extension_code'] == 'exchange_goods') {
			$order['integral_money'] = 0;
			$order['integral']		 = $total['exchange_integral'];
		}

		$order['from_ad'] = ! empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
		$order['referer'] = ! empty($options['device']['client']) ? $options['device']['client'] : 'mobile';

		/* 记录扩展信息 */
		if ($options['flow_type'] != CART_GENERAL_GOODS) {
			$order['extension_code'] = $_SESSION['extension_code'];
			$order['extension_id'] = $_SESSION['extension_id'];
		}

		$parent_id = 0;
		$order['parent_id'] = $parent_id;

		/* 插入订单表 */
		$order['order_sn'] = cart::get_order_sn(); // 获取新订单号
		//$db_order_info	= RC_Model::model('orders/order_info_model');
		$db_order_info = RC_DB::table('order_info');
		/*过滤没有的字段*/
		unset($order['need_inv']);
		unset($order['need_insure']);
		unset($order['address_id']);
		unset($order['address_name']);
		unset($order['audit']);
		unset($order['longitude']);
		unset($order['latitude']);
		unset($order['address_info']);
		unset($order['cod_fee']);

		$new_order_id	= $db_order_info->insertGetId($order);
		$order['order_id'] = $new_order_id;

		/* 插入订单商品 */
		//$db_order_goods = RC_Model::model('orders/order_goods_model');
		//$db_goods_activity = RC_Model::model('goods/goods_activity_model');
		$db_order_goods = RC_DB::table('order_goods');
		$db_goods_activity = RC_DB::table('goods_activity');

		$field = 'goods_id, goods_name, goods_sn, product_id, goods_number, market_price,goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id, store_id';

		$data_row = RC_DB::table('cart')
			->selectRaw($field)
			->where('user_id', $_SESSION['user_id'])
			->where('rec_type', $options['flow_type'])
			->whereIn('rec_id', $options['cart_id'])
			->get();
		
		if (!empty($data_row)) {
			foreach ($data_row as $row) {
				$arr = array(
					'order_id'		=> $new_order_id,
					'goods_id'		=> $row['goods_id'],
					'goods_name'	=> $row['goods_name'],
					'goods_sn'		=> $row['goods_sn'],
					'product_id'	=> $row['product_id'],
					'goods_number'	=> $row['goods_number'],
					'market_price'	=> $row['market_price'],
					'goods_price'	=> $row['goods_price'],
					'goods_attr'	=> $row['goods_attr'],
					'is_real'		=> $row['is_real'],
					'extension_code' => $row['extension_code'],
					'parent_id'		=> $row['parent_id'],
					'is_gift'		=> $row['is_gift'],
					'goods_attr_id' => $row['goods_attr_id'],
				);
				$db_order_goods->insert($arr);
			}
		}

		/* 如果使用库存，且下订单时减库存，则减少库存 */
		if (ecjia::config('use_storage') == '1' && ecjia::config('stock_dec_time') == SDT_PLACE) {
			$result = cart::change_order_goods_storage($order['order_id'], true, SDT_PLACE);
			if (is_ecjia_error($result)) {
				/* 库存不足删除已生成的订单（并发处理） will.chen*/
				//$db_order_info->where(array('order_id' => $order['order_id']))->delete();
				//$db_order_goods->where(array('order_id' => $order['order_id']))->delete();
				$db_order_info->where('order_id', $order['order_id'])->delete();
				$db_order_goods->where('order_id', $order['order_id'])->delete();
				return $result;
			}
		}

		/* 修改拍卖活动状态 */
		if ($order['extension_code'] == 'auction') {
			//$db_goods_activity->where(array('act_id' => $order['extension_id']))->update(array('is_finished' => 2));
			$db_goods_activity->where('act_id', $order['extension_id'])->update(array('is_finished' => 2));
		}

		/* 处理积分、红包 */
		if ($order['user_id'] > 0 && $order['integral'] > 0) {
			$options = array(
				'user_id'		=> $order['user_id'],
				'pay_points'	=> $order['integral'] * (- 1),
				'change_desc'	=> sprintf(RC_Lang::get('cart::shopping_flow.pay_order'), $order['order_sn'])
			);
			$result = RC_Api::api('user', 'account_change_log', $options);
			if (is_ecjia_error($result)) {
				return new ecjia_error('integral_error', '积分使用失败！');
			}
		}

		if ($order['bonus_id'] > 0 && $temp_amout > 0) {
			RC_Api::api('bonus', 'use_bonus', array('bonus_id' => $order['bonus_id'], 'order_id' => $new_order_id));
		}

		/* 给商家发邮件 */
		/* 增加是否给客服发送邮件选项 */
		if (ecjia::config('send_service_email') && ecjia::config('service_email') != '') {
			$tpl_name = 'remind_of_new_order';
			$tpl   = RC_Api::api('mail', 'mail_template', $tpl_name);

			ecjia_front::$controller->assign('order', $order);
			ecjia_front::$controller->assign('goods_list', $cart_goods);
			ecjia_front::$controller->assign('shop_name', ecjia::config('shop_name'));
			ecjia_front::$controller->assign('send_date', date(ecjia::config('time_format')));

			$content = ecjia_front::$controller->fetch_string($tpl['template_content']);
			RC_Mail::send_mail(ecjia::config('shop_name'), ecjia::config('service_email'), $tpl['template_subject'], $content, $tpl['is_html']);
		}

		$result = ecjia_app::validate_application('sms');
		if (!is_ecjia_error($result)) {
			/* 如果需要，发短信 */
			if (ecjia::config('sms_order_placed')== '1' && ecjia::config('sms_shop_mobile') != '') {
				//发送短信
				$tpl_name = 'order_placed_sms';
				$tpl   = RC_Api::api('sms', 'sms_template', $tpl_name);
				if (!empty($tpl)) {
					ecjia_front::$controller->assign('order', $order);
					ecjia_front::$controller->assign('consignee', $order['consignee']);
					ecjia_front::$controller->assign('mobile', $order['mobile']);

					$content = ecjia_front::$controller->fetch_string($tpl['template_content']);
					$msg = $order['pay_status'] == PS_UNPAYED ? $content : $content.__('已付款');
					$options = array(
						'mobile' 		=> ecjia::config('sms_shop_mobile'),
						'msg'			=> $msg,
						'template_id' 	=> $tpl['template_id'],
					);
					$response = RC_Api::api('sms', 'sms_send', $options);
				}
			}
		}
		/* 如果订单金额为0 处理虚拟卡 */
		if ($order['order_amount'] <= 0) {
			$rec_type = $options['flow_type'];
			$user_id  = $_SESSION['user_id'];
			if ($user_id) {
			    $res = RC_DB::table('cart')
			    	->select(RC_DB::raw('goods_id, goods_name, goods_number AS num'))
			  		->where('is_real', 0)
			        ->where('extension_code', 'virtual_card')
			     	->where('is_real', $rec_type)
			  		->where('user_id', $user_id)
			 		->get();
			} else {
				$session_id = SESS_ID;
				$res = RC_DB::table('cart')
					->select(RC_DB::raw('goods_id, goods_name, goods_number AS num'))
       				->where('is_real', 0)
                  	->where('extension_code', 'virtual_card')
                  	->where('is_real', $rec_type)
                 	->where('session_id', $session_id)
                  	->get();
			}

			$virtual_goods = array();
			foreach ($res as $row) {
				$virtual_goods['virtual_card'][] = array(
					'goods_id' 		=> $row['goods_id'],
					'goods_name' 	=> $row['goods_name'],
					'num' 			=> $row['num']
				);
			}

			if ($virtual_goods and $options['flow_type'] != CART_GROUP_BUY_GOODS) {
				/* 如果没有实体商品，修改发货状态，送积分和红包 */
				$count = $db_order_goods
					->where('order_id', $order['order_id'])
					->where('is_real', '=', 1)
					->count();
				if ($count <= 0) {
					/* 修改订单状态 */
					update_order($order['order_id'], array(
					'shipping_status' => SS_SHIPPED,
					'shipping_time' => RC_Time::gmtime()
					));

					/* 如果订单用户不为空，计算积分，并发给用户；发红包 */
					if ($order['user_id'] > 0) {
						/* 取得用户信息 */
						$user = user_info($order['user_id']);
						/* 计算并发放积分 */
						$integral = integral_to_give($order);
						$options = array(
								'user_id' =>$order['user_id'],
								'rank_points' => intval($integral['rank_points']),
								'pay_points' => intval($integral['custom_points']),
								'change_desc' =>sprintf(RC_Lang::get('orders::order.order_gift_integral'), $order['order_sn'])
						);
						$result = RC_Api::api('user', 'account_change_log', $options);
						if (is_ecjia_error($result)) {
                        	return $result;
						}
						/* 发放红包 */
						send_order_bonus($order['order_id']);
					}
				}
			}
			$result = ecjia_app::validate_application('sms');
		}

		/* 清空购物车 */
		cart::clear_cart($options['flow_type'], $options['cart_id']);

		/* 插入支付日志 */
		$order['log_id'] = $payment_method->insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);

		$payment_info = $payment_method->payment_info_by_id($order['pay_id']);

		if (! empty($order['shipping_name'])) {
			$order['shipping_name'] = trim(stripcslashes($order['shipping_name']));
		}

		/* 订单信息 */
		unset($_SESSION['flow_consignee']); // 清除session中保存的收货人信息
		unset($_SESSION['flow_order']);
		unset($_SESSION['direct_shopping']);
		$subject = $cart_goods[0]['goods_name'] . '等' . count($cart_goods) . '种商品';
		$order_info = array(
			'order_sn'   => $order['order_sn'],
			'order_id'   => $order['order_id'],
			'order_info' => array(
				'pay_code'               => $payment_info['pay_code'],
				'order_amount'           => $order['order_amount'],
		        'formatted_order_amount' => price_format($order['order_amount']),
				'order_id'               => $order['order_id'],
				'subject'                => $subject,
				'desc'                   => $subject,
				'order_sn'               => $order['order_sn']
			)
		);
		RC_DB::table('order_status_log')->insert(array(
			'order_status'	=> RC_Lang::get('cart::shopping_flow.label_place_order'),
			'order_id'		=> $order['order_id'],
			'message'		=> '下单成功，订单号：'.$order['order_sn'],
			'add_time'		=> RC_Time::gmtime(),
		));

		if (!$payment_info['is_cod'] && $order['order_amount'] > 0) {
			RC_DB::table('order_status_log')->insert(array(
				'order_status'	=> RC_Lang::get('cart::shopping_flow.unpay'),
				'order_id'		=> $order['order_id'],
				'message'		=> '请尽快支付该订单，超时将会自动取消订单',
				'add_time'		=> RC_Time::gmtime(),
			));
		}
		
		if ($payment_info['is_cod']) {
			RC_DB::table('order_status_log')->insert(array(
				'order_status'	=> RC_Lang::get('cart::shopping_flow.merchant_process'),
				'order_id'		=> $order['order_id'],
				'message'		=> '订单已通知商家，等待商家处理',
				'add_time'		=> RC_Time::gmtime(),
			));
		}
		
		/* 客户下单通知（默认通知店长）*/
		/* 获取店长的记录*/
		$devic_info = $staff_user = array();
		$staff_user = RC_DB::table('staff_user')->where('store_id', $order['store_id'])->where('parent_id', 0)->first();
		if (!empty($staff_user)) {
			$devic_info = RC_Api::api('mobile', 'device_info', array('user_type' => 'merchant', 'user_id' => $staff_user['user_id']));
		}
		
		if (!is_ecjia_error($devic_info) && !empty($devic_info)) {
			$push_event = RC_Model::model('push/push_event_viewmodel')->where(array('event_code' => 'order_placed', 'is_open' => 1, 'status' => 1, 'mm.app_id is not null', 'mt.template_id is not null', 'device_code' => $devic_info['device_code'], 'device_client' => $devic_info['device_client']))->find();
			
			if (!empty($push_event)) {
				/* 通知记录*/
				$orm_staff_user_db = RC_Model::model('express/orm_staff_user_model');
				$staff_user_ob = $orm_staff_user_db->find($staff_user['user_id']);
				
				$order_data = array(
					'title'	=> '客户下单',
					'body'	=> '您有一笔新订单，订单号为：'.$order['order_sn'],
					'data'	=> array(
						'order_id'		         => $order['order_id'],
						'order_sn'		         => $order['order_sn'],
						'order_amount'	         => $order['order_amount'],
						'formatted_order_amount' => price_format($order['order_amount']),
						'consignee'		         => $order['consignee'],
						'mobile'		         => $order['mobile'],
						'address'		         => $order['address'],
						'order_time'	         => RC_Time::local_date(ecjia::config('time_format'), $order['add_time']),
					),
				);
				
				$push_order_placed = new OrderPlaced($order_data);
				RC_Notification::send($staff_user_ob, $push_order_placed);
				
				RC_Loader::load_app_class('push_send', 'push', false);
				ecjia_admin::$controller->assign('order', $order);
				$content = ecjia_admin::$controller->fetch_string($push_event['template_content']);
					
				if ($devic_info['device_client'] == 'android') {
					$result = push_send::make($push_event['app_id'])->set_client(push_send::CLIENT_ANDROID)->set_field(array('open_type' => 'admin_message'))->send($devic_info['device_token'], $push_event['template_subject'], $content, 0, 1);
				} elseif ($devic_info['device_client'] == 'iphone') {
					$result = push_send::make($push_event['app_id'])->set_client(push_send::CLIENT_IPHONE)->set_field(array('open_type' => 'admin_message'))->send($devic_info['device_token'], $push_event['template_subject'], $content, 0, 1);
				}
			}
		}
		return $order_info;
	}
}

// end