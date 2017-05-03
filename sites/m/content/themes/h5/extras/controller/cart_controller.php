<?php
//
//    ______         ______           __         __         ______
//   /\  ___\       /\  ___\         /\_\       /\_\       /\  __ \
//   \/\  __\       \/\ \____        \/\_\      \/\_\      \/\ \_\ \
//    \/\_____\      \/\_____\     /\_\/\_\      \/\_\      \/\_\ \_\
//     \/_____/       \/_____/     \/__\/_/       \/_/       \/_/ /_/
//
//   上海商创网络科技有限公司
//
//  ---------------------------------------------------------------------------------
//
//   一、协议的许可和权利
//
//    1. 您可以在完全遵守本协议的基础上，将本软件应用于商业用途；
//    2. 您可以在协议规定的约束和限制范围内修改本产品源代码或界面风格以适应您的要求；
//    3. 您拥有使用本产品中的全部内容资料、商品信息及其他信息的所有权，并独立承担与其内容相关的
//       法律义务；
//    4. 获得商业授权之后，您可以将本软件应用于商业用途，自授权时刻起，在技术支持期限内拥有通过
//       指定的方式获得指定范围内的技术支持ECJia；
//
//   二、协议的约束和限制
//
//    1. 未获商业授权之前，禁止将本软件用于商业用途（包括但不限于企业法人经营的产品、经营性产品
//       以及以盈利为目的或实现盈利产品）；
//    2. 未获商业授权之前，禁止在本产品的整体或在任何部分基础上发展任何派生版本、修改版本或第三
//       方版本用于重新开发；
//    3. 如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回并承担相应法律责任；
//
//   三、有限担保和免责声明
//
//    1. 本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的；
//    2. 用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未获得商业授权之前，我们不承
//       诺提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任；
//    3. 上海商创网络科技有限公司不对使用本产品构建的商城中的内容信息承担责任，但在不侵犯用户隐
//       私信息的前提下，保留以任何方式获取用户信息及商品信息的权利；
//
//   有关本产品最终用户授权协议、商业授权与技术ECJia的详细内容，均由上海商创网络科技有限公司独家
//   提供。上海商创网络科技有限公司拥有在不事先通知的情况下，修改授权协议的权力，修改后的协议对
//   改变之日起的新授权用户生效。电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和
//   等同的法律效力。您一旦开始修改、安装或使用本产品，即被视为完全理解并接受本协议的各项条款，
//   在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本
//   授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。
//
//  ---------------------------------------------------------------------------------
//
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * 购物车模块控制器代码
 */
class cart_controller {
    /**
     * 购物车列表
     */
    public static function init() {
    	$addr = $_GET['addr'];
    	$name = $_GET['name'];
    	$latng = explode(",", $_GET['latng']) ;
    	$longitude = !empty($latng[1]) ? $latng[1] : $_COOKIE['longitude'];
    	$latitude  = !empty($latng[0]) ? $latng[0] : $_COOKIE['latitude'];
    	
    	if (!empty($addr)) {
    		setcookie("location_address", $addr);
        	setcookie("location_name", $name);
        	setcookie("longitude", $longitude);
        	setcookie("latitude", $latitude);
        	setcookie("location_address_id", 0);
    		return ecjia_front::$controller->redirect(RC_Uri::url('cart/index/init'));
    	}
    	
    	$token = ecjia_touch_user::singleton()->getToken();
    	$arr = array(
    		'token' 	=> $token,
    		'location' 	=> array('longitude' => $longitude, 'latitude' => $latitude)
    	);
    	//店铺购物车商品
    	$cart_list = ecjia_touch_manager::make()->api(ecjia_touch_api::CART_LIST)->data($arr)->run();
    	if (!empty($cart_list['cart_list'])) {
    		foreach ($cart_list['cart_list'] as $k => $v) {
    			$cart_list['cart_list'][$k]['total']['check_all'] = true;
    			$cart_list['cart_list'][$k]['total']['check_one'] = false;
    			
    			if (!empty($v['goods_list'])) {
    				foreach ($v['goods_list'] as $key => $val) {
    					if ($val['is_checked'] == 0) {
    						$cart_list['cart_list'][$k]['total']['check_all'] = false;	//全部选择
    					} elseif ($val['is_disabled'] == 0) {
    						$cart_list['cart_list'][$k]['total']['check_one'] = true;	//至少选择了一个
    					}
    					
    					if ($val['is_disabled'] == 0 && $val['is_checked'] == 1) {
    						if ($key == 0) {
    							$cart_list['cart_list'][$k]['total']['data_rec'] = $val['rec_id'];
    						} else {
    							$cart_list['cart_list'][$k]['total']['data_rec'] .= ','.$val['rec_id'];
    						}
    					}
    					$cart_list['cart_list'][$k]['total']['data_rec'] = trim($cart_list['cart_list'][$k]['total']['data_rec'], ',');
    				}
    			}
    		}
    	}

    	ecjia_front::$controller->assign('cart_list', $cart_list['cart_list']);
    	ecjia_front::$controller->assign('referer_url', urlencode(RC_Uri::url('cart/index/init')));
    	
    	if (!ecjia_touch_user::singleton()->isSignin()) {
    		ecjia_front::$controller->assign('not_login', true);
    	}
    	
    	if (isset($_COOKIE['location_address_id']) && $_COOKIE['location_address_id'] > 0) {
    		$address_info = user_function::address_info(ecjia_touch_user::singleton()->getToken(), $_COOKIE['location_address_id']);
    		ecjia_front::$controller->assign('address_info', $address_info);
            ecjia_front::$controller->assign('address_id', $_COOKIE['location_address_id']);
    	}
    	
        ecjia_front::$controller->assign_lang();
    	ecjia_front::$controller->assign('active', 'cartList');
    	
    	ecjia_front::$controller->assign_title('购物车列表');
        ecjia_front::$controller->display('cart_list.dwt');
    }
    
    public static function update_cart() {
    	if (!ecjia_touch_user::singleton()->isSignin()) {
    		$url = RC_Uri::site_url() . substr($_SERVER['HTTP_REFERER'], strripos($_SERVER['HTTP_REFERER'], '/'));
    		$referer_url = RC_Uri::url('user/privilege/login', array('referer_url' => urlencode($url)));
    		return ecjia_front::$controller->showmessage('', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR, array('referer_url' => $referer_url));
    	}
    	 
    	$rec_id 	= is_array(($_POST['rec_id'])) ? $_POST['rec_id'] : $_POST['rec_id'];
    	$new_number = intval($_POST['val']);
    	$store_id 	= intval($_POST['store_id']);
    	$goods_id   = intval($_POST['goods_id']);
    	$checked	= isset($_POST['checked']) ? $_POST['checked'] : '';
    	$response   = isset($_POST['response']) ? true : false;
    
    	$token = ecjia_touch_user::singleton()->getToken();
    	$arr = array(
    		'token' 	=> $token,
    		'location' 	=> array('longitude' => $_COOKIE['longitude'], 'latitude' => $_COOKIE['latitude']),
    	);
    	if (!empty($store_id)) {
    		$arr['seller_id'] = $store_id;
    	}
    	//修改购物车中商品选中状态
    	if ($checked !== '') {
    		if (is_array($rec_id)) {
    			$arr['rec_id'] = implode(',', $rec_id);
    		} else {
    			$arr['rec_id'] = $rec_id;
    		}
    		$arr['is_checked'] = $checked;
    		ecjia_touch_manager::make()->api(ecjia_touch_api::CART_CHECKED)->data($arr)->run();
    	} else {
    		//清空购物车
    		if (is_array($rec_id)) {
    			$arr['rec_id'] = implode(',', $rec_id);
    			$data = ecjia_touch_manager::make()->api(ecjia_touch_api::CART_DELETE)->data($arr)->run();
    
    			return ecjia_front::$controller->showmessage('', ecjia::MSGSTAT_SUCCESS | ecjia::MSGTYPE_JSON, array('empty' => true, 'store_id' => $store_id));
    		} else {
    			if (!empty($new_number)) {
    				$arr['new_number'] = $new_number;
    				if (!empty($rec_id)) {
    					//更新购物车中商品
    					$arr['rec_id'] = $rec_id;
    					$data = ecjia_touch_manager::make()->api(ecjia_touch_api::CART_UPDATE)->data($arr)->send()->getBody();
    					$data = json_decode($data, true);
    					if ($data['status']['succeed'] == 0) {
    						return ecjia_front::$controller->showmessage($data['status']['error_desc'], ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
    					}
    				} elseif (!empty($goods_id)) {
    					//添加商品到购物车
    					$arr['goods_id'] = $goods_id;
    					$data = ecjia_touch_manager::make()->api(ecjia_touch_api::CART_CREATE)->data($arr)->send()->getBody();
    					$data = json_decode($data, true);
    					if ($data['status']['succeed'] == 0) {
    						return ecjia_front::$controller->showmessage($data['status']['error_desc'], ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
    					}
    				}
    			} else {
    				if (!empty($rec_id)) {
    					//从购物车中删除商品
    					$arr['rec_id'] = $rec_id;
    					ecjia_touch_manager::make()->api(ecjia_touch_api::CART_DELETE)->data($arr)->run();
    				}
    			}
    		}
    	}
    	 
    	$paramater = array(
    		'token' 	=> $token,
    		'seller_id' => $store_id,
    		'location' 	=> array('longitude' => $_COOKIE['longitude'], 'latitude' => $_COOKIE['latitude'])
    	);
    	 
    	//店铺购物车商品
    	$cart_list = ecjia_touch_manager::make()->api(ecjia_touch_api::CART_LIST)->data($paramater)->run();
    	$cart_goods_list = $cart_list['cart_list'][0]['goods_list'];
    	$cart_count = $cart_list['cart_list'][0]['total'];
    
    	$data_rec = '';
    	if (!empty($cart_goods_list)) {
    		foreach ($cart_goods_list as $k => $v) {
    			if ($v['is_disabled'] == 0 && $v['is_checked'] == 1) {
    				if ($k == 0) {
    					$data_rec = $v['rec_id'];
    				} else {
    					$data_rec .= ','.$v['rec_id'];
    				}
    			}
    		}
    		$data_rec = trim($data_rec, ',');
    	}
    	 
    	//购物车列表 切换状态直接返回
    	if ($response) {
    		return ecjia_front::$controller->showmessage('', ecjia::MSGSTAT_SUCCESS | ecjia::MSGTYPE_JSON, array('count' => $cart_count, 'response' => $response, 'data_rec' => $data_rec));
    	}
    	 
    	$sayList = '';
    	if ($_POST['checked'] === '') {
    		ecjia_front::$controller->assign('list', $cart_goods_list);
    		$sayList = ecjia_front::$controller->fetch('merchant.dwt');
    	}
    
    	return ecjia_front::$controller->showmessage('', ecjia::MSGSTAT_SUCCESS | ecjia::MSGTYPE_JSON, array('say_list' => $sayList, 'list' => $cart_goods_list, 'count' => $cart_count, 'data_rec' => $data_rec));
    }


    /**
     * 订单确认
     */
    public static function checkout() {
        
        if (!ecjia_touch_user::singleton()->isSignin()) {
            return ecjia_front::$controller->showmessage('请先登录再继续操作', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        
        $address_id = empty($_REQUEST['address_id']) ? 0 : intval($_REQUEST['address_id']);
        $rec_id = empty($_REQUEST['rec_id']) ? 0 : trim($_REQUEST['rec_id']);
        
        $url = RC_Uri::site_url() . substr($_SERVER['REQUEST_URI'], strripos($_SERVER['REQUEST_URI'], '/'));
        
        $pjax_url = RC_Uri::url('cart/flow/checkout', array('address_id' => $address_id, 'rec_id' => $rec_id));
        if(empty($rec_id)) {
            return ecjia_front::$controller->showmessage('请选择商品再进行结算', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        if (empty($address_id)) {
            return ecjia_front::$controller->showmessage('请选择收货地址', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        
        $params_cart = array(
            'token' => ecjia_touch_user::singleton()->getToken(),
            'address_id' => $address_id,
            'rec_id' => $rec_id,
            'location' => array(
                'longitude' => $_COOKIE['longitude'],
                'latitude' => $_COOKIE['latitude']
            ),
        );
        $rs = ecjia_touch_manager::make()->api(ecjia_touch_api::FLOW_CHECKORDER)->data($params_cart)
        ->send()->getBody();
        $rs = json_decode($rs,true);
        if (! $rs['status']['succeed']) {
            $url = RC_Uri::url('cart/index/init');
            return ecjia_front::$controller->showmessage($rs['status']['error_desc'], ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT,array('pjaxurl' => $url));
        }
        //红包改键
        if ($rs['data']['bonus']) {
            $rs['data']['bonus'] = touch_function::change_array_key($rs['data']['bonus'], 'bonus_id');
        }
        if ($rs['data']['payment_list']) {
            $rs['data']['payment_list'] = touch_function::change_array_key($rs['data']['payment_list'], 'pay_id');
        }
        if ($rs['data']['shipping_list']) {
            $rs['data']['shipping_list'] = touch_function::change_array_key($rs['data']['shipping_list'], 'shipping_id');
        }
        $cart_key = md5($address_id.$rec_id);
        $_SESSION['cart'][$cart_key]['data'] = $rs['data'];
        //支付方式
        $payment_id = 0;
        if ($_POST['payment_update']) {
            $payment_id = $_SESSION['cart'][$cart_key]['temp']['pay_id'] = empty($_POST['payment']) ? 0 : intval($_POST['payment']);
        } else {
            if (isset($_SESSION['cart'][$cart_key]['temp']['pay_id'])) {
                $payment_id = $_SESSION['cart'][$cart_key]['temp']['pay_id'];
            }
        }
        if ($payment_id) {
            $selected_payment = $_SESSION['cart'][$cart_key]['data']['payment_list'][$payment_id];
        } else {
            $selected_payment = array();
            if ($rs['data']['payment_list']) {
                foreach ($rs['data']['payment_list'] as $payment) {
                    $selected_payment = $payment;break;
                }
            }
        }
        
        //配送方式
        $shipping_id = 0;
        if ($_POST['shipping_update']) {
            $shipping_id = $_SESSION['cart'][$cart_key]['temp']['shipping_id'] = empty($_POST['shipping']) ? 0 : intval($_POST['shipping']);
        } else {
            if (isset($_SESSION['cart'][$cart_key]['temp']['shipping_id'])) {
                $shipping_id = $_SESSION['cart'][$cart_key]['temp']['shipping_id'];
            }
        }
        if ($shipping_id) {
            $selected_shipping = $_SESSION['cart'][$cart_key]['data']['shipping_list'][$shipping_id];
        } else {
            $selected_shipping = array();
            if ($rs['data']['shipping_list']) {
                foreach ($rs['data']['shipping_list'] as $tem_shipping) {
                    $selected_shipping = $tem_shipping;break;
                }
            }
        }
        if (isset($selected_shipping['shipping_date'])) {
            $selected_shipping['shipping_date_enable'] = 1;
        }
        //配送时间
        if ($_POST['shipping_date_update']) {
            $_SESSION['cart'][$cart_key]['temp']['shipping_date'] = empty($_POST['shipping_date']) ? '' : trim($_POST['shipping_date']);
            $_SESSION['cart'][$cart_key]['temp']['shipping_time'] = empty($_POST['shipping_time']) ? '' : trim($_POST['shipping_time']);
        } else {
            if ($selected_shipping['shipping_date_enable']) {
                $_SESSION['cart'][$cart_key]['temp']['shipping_date'] = $selected_shipping['shipping_date'][0]['date'];
                $_SESSION['cart'][$cart_key]['temp']['shipping_time'] = $selected_shipping['shipping_date'][0]['time'][0]['start_time'] . '-' . $selected_shipping['shipping_date'][0]['time'][0]['end_time'];
            }
        }
        
        //发票
        if ($_POST['inv_update']) {
            if (empty($_POST['inv_content']) || empty($_POST['inv_type']) || empty($_POST['inv_payee'])) {
                return ecjia_front::$controller->showmessage('请填写完整的发票信息', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR, array('pjaxurl' => ''));
            }
            $_SESSION['cart'][$cart_key]['temp']['inv_payee'] = empty($_POST['inv_payee']) ? '' : trim($_POST['inv_payee']);
            $_SESSION['cart'][$cart_key]['temp']['inv_content'] = empty($_POST['inv_content']) ? '' : trim($_POST['inv_content']);
            $_SESSION['cart'][$cart_key]['temp']['inv_type'] = empty($_POST['inv_type']) ? '' : trim($_POST['inv_type']);
            $_SESSION['cart'][$cart_key]['temp']['need_inv'] = 1;
        }
        //发票清空
        if ($_POST['inv_clear']) {
            $_SESSION['cart'][$cart_key]['temp']['inv_payee'] = '';
            $_SESSION['cart'][$cart_key]['temp']['inv_content'] = '';
            $_SESSION['cart'][$cart_key]['temp']['inv_type'] = '';
            $_SESSION['cart'][$cart_key]['temp']['need_inv'] = 0;
        }
        
        //留言
        if ($_POST['note_update']) {
            $_SESSION['cart'][$cart_key]['temp']['note'] = empty($_POST['note']) ? '' : trim($_POST['note']);
        }
        //红包
        if ($_POST['bonus_update']) {
            $_SESSION['cart'][$cart_key]['temp']['bonus'] = empty($_POST['bonus']) ? '' : trim($_POST['bonus']);
        }
        //红包清空
        if ($_POST['bonus_clear']) {
            $_SESSION['cart'][$cart_key]['temp']['bonus'] = '';
        }
        
        //积分
        if ($_POST['integral_update']) {
            if ($_POST['integral'] >  $_SESSION['cart'][$cart_key]['data']['order_max_integral']) {
                return ecjia_front::$controller->showmessage('积分使用超出订单限制', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
            } else if ($_POST['integral'] >  $_SESSION['cart'][$cart_key]['data']['your_integral']) {
                return ecjia_front::$controller->showmessage('积分不足', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
            } else {
                $_SESSION['cart'][$cart_key]['temp']['integral'] = empty($_POST['integral']) ? 0 : intval($_POST['integral']);
                return ecjia_front::$controller->showmessage('', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => $pjax_url));
            }
        }
        
        //total
        $total['goods_number'] = 0;
        $total['goods_price'] = 0;
        foreach ($rs['data']['goods_list'] as $item) {
            $total['goods_number'] += $item['goods_number'];
            $total['goods_price'] += $item['subtotal'];
        }
        $total['goods_price_formated'] = price_format($total['goods_price']);
        $total['shipping_fee'] = $selected_shipping['shipping_fee']; //$rs['data']['shipping_list'];
        $total['shipping_fee_formated'] = price_format($total['shipping_fee']);
        $total['discount_bonus'] = 0;
        if ($_SESSION['cart'][$cart_key]['temp']['bonus']) {
            $temp_bonus_id = $_SESSION['cart'][$cart_key]['temp']['bonus'];
            $total['discount_bonus'] = $_SESSION['cart'][$cart_key]['data']['bonus'][$temp_bonus_id]['bonus_amount'];
        }
        $total['discount_integral'] = 0;
        if ($_SESSION['cart'][$cart_key]['temp']['integral']) {
            $total['discount_integral'] = $_SESSION['cart'][$cart_key]['temp']['integral']/100;
        }

        $total['discount'] = $rs['data']['discount'] + $total['discount_bonus'] + $total['discount_integral'];//优惠金额 -红包 -积分
        $total['discount_formated'] = price_format($total['discount']);
        
        $total['pay_fee'] = $selected_payment['pay_fee']; 
        $total['pay_fee_formated'] = price_format($total['pay_fee']);
        $total['amount'] = $total['goods_price'] + $total['shipping_fee'] + $total['pay_fee'] - $total['discount']; 
        //发票税费
        $total['tax_fee'] = 0;
        if ($_SESSION['cart'][$cart_key]['temp']['inv_type']) {
            foreach ($_SESSION['cart'][$cart_key]['data']['inv_type_list'] as $type) {
                if ($type['label_value'] == $_SESSION['cart'][$cart_key]['temp']['inv_type']) {
                    $rate = floatval($type['rate']) / 100;
                    $total['tax_fee'] = $rate * $total['goods_price'];
                    $total['tax_fee'] = round($total['tax_fee'], 2);
                    break;
                }
            }
        }
        $total['tax_fee_formated'] = price_format($total['tax_fee']);
        $total['amount'] += $total['tax_fee'];
        $total['amount_formated'] = price_format($total['amount']);

        ecjia_front::$controller->assign('data', $rs['data']);
        ecjia_front::$controller->assign('total_goods_number', $total['goods_number']);
        ecjia_front::$controller->assign('selected_payment', $selected_payment);
        ecjia_front::$controller->assign('selected_shipping', $selected_shipping);
        ecjia_front::$controller->assign('total', $total);
        ecjia_front::$controller->assign('address_id', $address_id);
        ecjia_front::$controller->assign('rec_id', $rec_id);
        ecjia_front::$controller->assign('temp', $_SESSION['cart'][$cart_key]['temp']);
        
        ecjia_front::$controller->assign('title', '结算');
        ecjia_front::$controller->assign_title('结算');
        ecjia_front::$controller->assign_lang();
        ecjia_front::$controller->display('flow_checkout.dwt');
    }

    //商品清单
    public static function goods_list() {
        if (!ecjia_touch_user::singleton()->isSignin()) {
            return ecjia_front::$controller->showmessage('请先登录再继续操作', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        $address_id = empty($_GET['address_id']) ? 0 : intval($_GET['address_id']);
        $rec_id = empty($_GET['rec_id']) ? 0 : trim($_GET['rec_id']);
        
        $url = RC_Uri::site_url() . substr($_SERVER['REQUEST_URI'], strripos($_SERVER['REQUEST_URI'], '/'));
        if(empty($rec_id)) {
            return ecjia_front::$controller->showmessage('请选择商品再进行结算', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        if (empty($address_id)) {
            return ecjia_front::$controller->showmessage('请选择收货地址', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        
        $params_cart = array(
            'token' => ecjia_touch_user::singleton()->getToken(),
            'address_id' => $address_id,
            'rec_id' => $rec_id,
            'location' => array(
                'longitude' => $_COOKIE['longitude'],
                'latitude' => $_COOKIE['latitude']
            ),
        );
        $rs = ecjia_touch_manager::make()->api(ecjia_touch_api::FLOW_CHECKORDER)->data($params_cart)
        ->send()->getBody();
        $rs = json_decode($rs,true);
        if (! $rs['status']['succeed']) {
            $url = '';
            return ecjia_front::$controller->showmessage($rs['status']['error_desc'], ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT,array('pjaxurl' => $url));
        }
        $total_goods_number = 0;
        foreach ($rs['data']['goods_list'] as $cart) {
            $total_goods_number += $cart['goods_number'];
        }

        ecjia_front::$controller->assign('list', $rs['data']['goods_list']);
        ecjia_front::$controller->assign('total_goods_number', $total_goods_number);
        
        ecjia_front::$controller->assign('title', '商品清单');
        ecjia_front::$controller->assign_title('商品清单');
        ecjia_front::$controller->assign_lang();
        ecjia_front::$controller->display('flow_goodslist.dwt');
    }

    /**
     *  提交订单
     */
    public static function done() {
        if (!ecjia_touch_user::singleton()->isSignin()) {
            return ecjia_front::$controller->showmessage('请先登录再继续操作', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
		$address_id = empty($_POST['address_id']) ? 0 : intval($_POST['address_id']);
		$rec_id = empty($_POST['rec_id']) ? 0 : trim($_POST['rec_id']);
        $pay_id = empty($_POST['pay_id']) ? 0 : intval($_POST['pay_id']);
        $shipping_id = empty($_POST['shipping_id']) ? 0 : intval($_POST['shipping_id']);
      	$shipping_date = empty($_POST['shipping_date']) ? '' : trim($_POST['shipping_date']);
   		$shipping_time = empty($_POST['shipping_time']) ? '' : trim($_POST['shipping_time']);
     	$inv_payee = empty($_POST['inv_payee']) ? '' : trim($_POST['inv_payee']);
 		$inv_content = empty($_POST['inv_content']) ? '' : trim($_POST['inv_content']);
     	$inv_type = empty($_POST['inv_type']) ? '' : trim($_POST['inv_type']);
       	$need_inv = empty($_POST['need_inv']) ? '' : trim($_POST['need_inv']);
		$postscript = empty($_POST['note']) ? '' : trim($_POST['note']);
   		$integral = empty($_POST['integral']) ? 0 : intval($_POST['integral']);
 		$bonus = empty($_POST['bonus']) ? 0 : intval($_POST['bonus']);
            
 		if (empty($rec_id)) {
     		return ecjia_front::$controller->showmessage('请选择商品再进行结算', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => ''));
		}
        if (empty($address_id)) {
 			return ecjia_front::$controller->showmessage('请选择收货地址', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => ''));
   		}
   		$expect_shipping_time = '';
   		if (!empty($shipping_date) || !empty($shipping_time)) {
   		    $expect_shipping_time = $shipping_date .' '. $shipping_time;
   		}
   		$params = array(
   			'token' 				=> ecjia_touch_user::singleton()->getToken(),
   			'address_id' 			=> $address_id,
   			'rec_id' 				=> $rec_id,
   			'shipping_id' 			=> $shipping_id,
   			'expect_shipping_time' 	=> $expect_shipping_time,
   			'pay_id' 				=> $pay_id,
   			'inv_payee'				=> $inv_payee,
   			'inv_type'				=> $inv_type,
   			'inv_content'			=> $inv_content,
   			'need_inv'      		=> $need_inv,
   			'postscript' 			=> $postscript,
   			'integral' 				=> $integral,
   			'bonus' 				=> $bonus,
   			'location' => array(
   				'longitude'	=> $_COOKIE['longitude'],
   				'latitude'  => $_COOKIE['latitude']
   			),
   		);
   		$rs = ecjia_touch_manager::make()->api(ecjia_touch_api::FLOW_DONE)->data($params)->send()->getBody();
   		$rs = json_decode($rs,true);
   		if (!$rs['status']['succeed']) {
   			$url = RC_Uri::url('cart/flow/checkout');
   			return ecjia_front::$controller->showmessage($rs['status']['error_desc'], ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => $url));
   		}
   		$order_id = $rs['data']['order_id'];
   		return ecjia_front::$controller->redirect(RC_Uri::url('pay/index/init', array('order_id' => $order_id, 'tips_show' => 1)));            
    }

    /**
     * 改变支付方式
     */
    public static function pay() {
        if (!ecjia_touch_user::singleton()->isSignin()) {
            return ecjia_front::$controller->showmessage('请先登录再继续操作', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        $address_id = empty($_GET['address_id']) ? 0 : intval($_GET['address_id']);
        $rec_id = empty($_GET['rec_id']) ? 0 : trim($_GET['rec_id']);
        
        $url = RC_Uri::site_url() . substr($_SERVER['REQUEST_URI'], strripos($_SERVER['REQUEST_URI'], '/'));
        if(empty($rec_id)) {
            return ecjia_front::$controller->showmessage('请选择商品再进行结算', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        if (empty($address_id)) {
            return ecjia_front::$controller->showmessage('请选择收货地址', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        
        $cart_key = md5($address_id.$rec_id);
        $data = $_SESSION['cart'][$cart_key]['data'];

        //分离线上支付线下支付
        $format_payment_list['online'] = array();
        $format_payment_list['offline'] = array();
        if ($data['payment_list']) {
            foreach ($data['payment_list'] as $tem_payment) {
                if ($tem_payment['is_online'] == 1) {
                    $format_payment_list['online'][$tem_payment['pay_id']] = $tem_payment;
                } else {
                    $format_payment_list['offline'][$tem_payment['pay_id']] = $tem_payment;
                }
            }
        }
        
        /*根据浏览器过滤支付方式，微信自带浏览器过滤掉支付宝支付，其他浏览器过滤掉微信支付*/
        if (!empty($format_payment_list)) {
        	if (cart_function::is_weixin() == true) {
        		foreach ($format_payment_list['online'] as $key => $val) {
        			if ($val['pay_code'] == 'pay_alipay') {
        				unset($format_payment_list['online'][$key]);
        			}
        		}
        	} else {
        		foreach ($format_payment_list['online'] as $key => $val) {
        			if ($val['pay_code'] == 'pay_wxpay') {
        				unset($format_payment_list['online'][$key]);
        			}
        		}
        	}
        }
        
        ecjia_front::$controller->assign('payment_list', $format_payment_list);
        ecjia_front::$controller->assign('address_id', $address_id);
        ecjia_front::$controller->assign('rec_id', $rec_id);
        ecjia_front::$controller->assign_lang();
        
        ecjia_front::$controller->assign('title', '支付方式');
        ecjia_front::$controller->assign_title('支付方式');
        ecjia_front::$controller->display('flow_pay.dwt');
    }

    /**
     * 改变配送方式
     */
    public static function shipping() {
        if (!ecjia_touch_user::singleton()->isSignin()) {
            return ecjia_front::$controller->showmessage('请先登录再继续操作', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        
        $address_id = empty($_GET['address_id']) ? 0 : intval($_GET['address_id']);
        $rec_id = empty($_GET['rec_id']) ? 0 : trim($_GET['rec_id']);
        
        $url = RC_Uri::site_url() . substr($_SERVER['REQUEST_URI'], strripos($_SERVER['REQUEST_URI'], '/'));
        if(empty($rec_id)) {
            return ecjia_front::$controller->showmessage('请选择商品再进行结算', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        if (empty($address_id)) {
            return ecjia_front::$controller->showmessage('请选择收货地址', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        
        $cart_key = md5($address_id.$rec_id);
        $data = $_SESSION['cart'][$cart_key]['data'];
        
        ecjia_front::$controller->assign('shipping_list', $data['shipping_list']);
        ecjia_front::$controller->assign('address_id', $address_id);
        ecjia_front::$controller->assign('rec_id', $rec_id);
        
        ecjia_front::$controller->assign_lang();
        ecjia_front::$controller->assign('title', '配送方式');
        ecjia_front::$controller->assign_title('配送方式');
        ecjia_front::$controller->display('flow_shipping.dwt');
    }
    
    public static function shipping_date() {
        if (!ecjia_touch_user::singleton()->isSignin()) {
            return ecjia_front::$controller->showmessage('请先登录再继续操作', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        $address_id = empty($_GET['address_id']) ? 0 : intval($_GET['address_id']);
        $rec_id = empty($_GET['rec_id']) ? 0 : trim($_GET['rec_id']);
        
        if(empty($rec_id)) {
            return ecjia_front::$controller->showmessage('请选择商品再进行结算', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => ''));
        }
        if (empty($address_id)) {
            return ecjia_front::$controller->showmessage('请选择收货地址', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => ''));
        }
        
        $cart_key = md5($address_id.$rec_id);
        $data = $_SESSION['cart'][$cart_key]['data'];

        $shipping = $data['shipping_list'][$_SESSION['cart'][$cart_key]['temp']['shipping_id']];
        if ($shipping['shipping_date']) {
            $shipping['shipping_date'] = touch_function::change_array_key($shipping['shipping_date'], 'date');
        }
        ecjia_front::$controller->assign('shipping', $shipping);
        ecjia_front::$controller->assign('address_id', $address_id);
        ecjia_front::$controller->assign('rec_id', $rec_id);
        ecjia_front::$controller->assign('temp', $_SESSION['cart'][$cart_key]['temp']);
        
        ecjia_front::$controller->assign_lang();
        ecjia_front::$controller->assign('title', '配送时间');
        ecjia_front::$controller->assign_title('配送时间');
        ecjia_front::$controller->display('flow_shipping_date.dwt');
    }

    /**
     * 开发票
     */
    public static function invoice() {
        if (!ecjia_touch_user::singleton()->isSignin()) {
            return ecjia_front::$controller->showmessage('请先登录再继续操作', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }

        $address_id = empty($_GET['address_id']) ? 0 : intval($_GET['address_id']);
        $rec_id = empty($_GET['rec_id']) ? 0 : trim($_GET['rec_id']);
        
        $url = RC_Uri::site_url() . substr($_SERVER['REQUEST_URI'], strripos($_SERVER['REQUEST_URI'], '/'));
        if(empty($rec_id)) {
            return ecjia_front::$controller->showmessage('请选择商品再进行结算', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        if (empty($address_id)) {
            return ecjia_front::$controller->showmessage('请选择收货地址', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        
        $cart_key = md5($address_id.$rec_id);
        $data = $_SESSION['cart'][$cart_key]['data'];
        $temp = $_SESSION['cart'][$cart_key]['temp'];

        ecjia_front::$controller->assign('inv_content_list', $data['inv_content_list']);
        ecjia_front::$controller->assign('inv_type_list', $data['inv_type_list']);
        ecjia_front::$controller->assign('temp', $temp);
        unset($data);unset($temp);
        ecjia_front::$controller->assign('address_id', $address_id);
        ecjia_front::$controller->assign('rec_id', $rec_id);
        
        ecjia_front::$controller->assign('title', '开发票');
        ecjia_front::$controller->assign_title('开发票');
        ecjia_front::$controller->display('flow_invoice.dwt');
    }

    /**
     * 增加订单留言
     */
    public static function note() {
        if (!ecjia_touch_user::singleton()->isSignin()) {
            return ecjia_front::$controller->showmessage('请先登录再继续操作', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        
        $address_id = empty($_GET['address_id']) ? 0 : intval($_GET['address_id']);
        $rec_id = empty($_GET['rec_id']) ? 0 : trim($_GET['rec_id']);
        
        $url = RC_Uri::site_url() . substr($_SERVER['REQUEST_URI'], strripos($_SERVER['REQUEST_URI'], '/'));
        if(empty($rec_id)) {
            return ecjia_front::$controller->showmessage('请选择商品再进行结算', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        if (empty($address_id)) {
            return ecjia_front::$controller->showmessage('请选择收货地址', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        
        $cart_key = md5($address_id.$rec_id);
        $data = $_SESSION['cart'][$cart_key]['temp'];
        ecjia_front::$controller->assign('note', $data['note']);
        ecjia_front::$controller->assign('address_id', $address_id);
        ecjia_front::$controller->assign('rec_id', $rec_id);

        ecjia_front::$controller->assign('title', '备注留言');
        ecjia_front::$controller->assign_title('备注留言');
        ecjia_front::$controller->display('flow_note.dwt');
    }

    /**
     * 选择使用红包
     */
    public static function bonus() {
        if (!ecjia_touch_user::singleton()->isSignin()) {
            return ecjia_front::$controller->showmessage('请先登录再继续操作', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        $address_id = empty($_GET['address_id']) ? 0 : intval($_GET['address_id']);
        $rec_id = empty($_GET['rec_id']) ? 0 : trim($_GET['rec_id']);
        
        $url = RC_Uri::site_url() . substr($_SERVER['REQUEST_URI'], strripos($_SERVER['REQUEST_URI'], '/'));
        if(empty($rec_id)) {
            return ecjia_front::$controller->showmessage('请选择商品再进行结算', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => ''));
        }
        if (empty($address_id)) {
            return ecjia_front::$controller->showmessage('请选择收货地址', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => ''));
        }
        
        $cart_key = md5($address_id.$rec_id);
        $data = $_SESSION['cart'][$cart_key]['data'];
        if ($data['allow_use_bonus'] == 0) {
            return ecjia_front::$controller->showmessage('红包不可用', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => ''));
        }
        ecjia_front::$controller->assign('data', $data);
        ecjia_front::$controller->assign('temp', $_SESSION['cart'][$cart_key]['temp']);
        ecjia_front::$controller->assign('address_id', $address_id);
        ecjia_front::$controller->assign('rec_id', $rec_id);
        
        ecjia_front::$controller->assign('title', '使用红包');
        ecjia_front::$controller->assign_title('使用红包');
        ecjia_front::$controller->display('flow_bonus.dwt');
    }

    /**
     * 使用积分
     */
    public static function integral() {
        if (!ecjia_touch_user::singleton()->isSignin()) {
            return ecjia_front::$controller->showmessage('请先登录再继续操作', ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('cart/index/init')));
        }
        $address_id = empty($_GET['address_id']) ? 0 : intval($_GET['address_id']);
        $rec_id = empty($_GET['rec_id']) ? 0 : trim($_GET['rec_id']);
        
        $url = RC_Uri::site_url() . substr($_SERVER['REQUEST_URI'], strripos($_SERVER['REQUEST_URI'], '/'));
        if(empty($rec_id)) {
            return ecjia_front::$controller->showmessage('请选择商品再进行结算', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => ''));
        }
        if (empty($address_id)) {
            return ecjia_front::$controller->showmessage('请选择收货地址', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => ''));
        }
        
        $cart_key = md5($address_id.$rec_id);
        $data = $_SESSION['cart'][$cart_key]['data'];
        if ($data['order_max_integral'] == 0) {
            return ecjia_front::$controller->showmessage('积分不可用', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => ''));
        }
        ecjia_front::$controller->assign('data', $data);
        ecjia_front::$controller->assign('temp', $_SESSION['cart'][$cart_key]['temp']);
        ecjia_front::$controller->assign('address_id', $address_id);
        ecjia_front::$controller->assign('rec_id', $rec_id);
        
        ecjia_front::$controller->assign_title('使用积分');
        ecjia_front::$controller->display('flow_integral.dwt');
    }
}

// end
