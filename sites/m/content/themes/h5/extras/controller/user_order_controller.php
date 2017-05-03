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
 * 订单模块控制器代码
 */
class user_order_controller {
    /**
     * 获取全部订单
     */
    public static function order_list() {
        $params_order = array('token' => ecjia_touch_user::singleton()->getToken(), 'pagination' => array('count' => 10, 'page' => 1), 'type' => '');
        $data = ecjia_touch_manager::make()->api(ecjia_touch_api::ORDER_LIST)->data($params_order)->run();
        
        ecjia_front::$controller->assign('order_list', $data);
        ecjia_front::$controller->assign_title('全部订单');
        ecjia_front::$controller->assign('title', '全部订单');
        ecjia_front::$controller->assign('active', 'orderList');
    	
        ecjia_front::$controller->assign_lang();
        ecjia_front::$controller->display('user_order_list.dwt');
    }
    
    /**
     * 订单详情
     */
    public static function order_detail() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (empty($order_id)) {
            return ecjia_front::$controller->showmessage('订单不存在', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_JSON);
        }
    
        $params_order = array('token' => ecjia_touch_user::singleton()->getToken(), 'order_id' => $order_id);
        $data = ecjia_touch_manager::make()->api(ecjia_touch_api::ORDER_DETAIL)->data($params_order)->run();
        
        ecjia_front::$controller->assign('order', $data);
        ecjia_front::$controller->assign('title', '订单详情');
        ecjia_front::$controller->assign_title('订单详情');
        ecjia_front::$controller->assign_lang();
        ecjia_front::$controller->display('user_order_detail.dwt');
    }

    /**
     * 取消订单
     */
    public static function order_cancel() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (empty($order_id)) {
            return ecjia_front::$controller->showmessage('订单不存在', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_JSON);
        }
        
        $params_order = array('token' => ecjia_touch_user::singleton()->getToken(), 'order_id' => $order_id);
        $data = ecjia_touch_manager::make()->api(ecjia_touch_api::ORDER_CANCEL)->data($params_order)->send()->getBody();
        $data = json_decode($data,true);

        $url = RC_Uri::url('user/order/order_detail', array('order_id' => $order_id));
        if ($data['status']['succeed']) {
            return ecjia_front::$controller->showmessage('取消订单成功', ecjia::MSGSTAT_SUCCESS | ecjia::MSGTYPE_ALERT,array('pjaxurl' => $url,'is_show' => false));
        } else {
            return ecjia_front::$controller->showmessage($data['status']['error_desc'], ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR,array('pjaxurl' => $url));
        }
        
    }
    
    // 再次购买 重新加入购物车
    public static function buy_again() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (empty($order_id)) {
            return ecjia_front::$controller->showmessage('订单不存在', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_JSON);
        }
        
        $params_order = array('token' => ecjia_touch_user::singleton()->getToken(), 'order_id' => $order_id);
        $data = ecjia_touch_manager::make()->api(ecjia_touch_api::ORDER_DETAIL)->data($params_order)->run();
        if (!$data) {
            return ecjia_front::$controller->showmessage('error', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_JSON);
        }
        if (isset($data['goods_list'])) {
            foreach ($data['goods_list'] as $goods) {
                $params_cart = array(
                    'token' => ecjia_touch_user::singleton()->getToken(),
                    'goods_id' => $goods['goods_id'],
                    'number' => $goods['goods_number'],
                    'location' => array(
                        'longitude' => $_COOKIE['longitude'],
                        'latitude' => $_COOKIE['latitude']
                    ),
                );
                $rs = ecjia_touch_manager::make()->api(ecjia_touch_api::CART_CREATE)->data($params_cart)
                ->send()->getBody();
                $rs = json_decode($rs,true);
                if (! $rs['status']['succeed']) {
                    if ($_GET['from'] == 'list') {
                        $url = RC_Uri::url('user/order/order_list');
                    } else {
                        $url = RC_Uri::url('user/order/order_detail', array('order_id' => $order_id));
                    }
                    $url = RC_Uri::url('user/order/order_detail', array('order_id' => $order_id));
                    return ecjia_front::$controller->showmessage($rs['status']['error_desc'], ecjia::MSGTYPE_ALERT | ecjia::MSGSTAT_ERROR,array('pjaxurl' => $url));
                }
            }
            $url = RC_Uri::url('cart/index/init');
            header('Location: ' . $url);
        }
    }
    
    /**
    * ajax获取订单
    */
    public static function async_order_list() {
        $size = intval($_GET['size']) > 0 ? intval($_GET['size']) : 10;
        $page = intval($_GET['page']) ? intval($_GET['page']) : 1;
        
        $params_order = array('token' => ecjia_touch_user::singleton()->getToken(), 'pagination' => array('count' => $size, 'page' => $page), 'type' => '');
        $data = ecjia_touch_manager::make()->api(ecjia_touch_api::ORDER_LIST)->data($params_order)
        ->send()->getBody();
        $data = json_decode($data, true);
        
        ecjia_front::$controller->assign('order_list', $data['data']);
        ecjia_front::$controller->assign_lang();
        $sayList = ecjia_front::$controller->fetch('user_order_list.dwt');
        return ecjia_front::$controller->showmessage('success', ecjia::MSGSTAT_SUCCESS | ecjia::MSGTYPE_JSON, array('list' => $sayList, 'page', 'is_last' => $data['paginated']['more'] ? 0 : 1));
    }

    /**
    * 确认收货
    */
    public static function affirm_received() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (empty($order_id)) {
            return ecjia_front::$controller->showmessage('订单不存在', ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_JSON);
        }
        
        $params_order = array('token' => ecjia_touch_user::singleton()->getToken(), 'order_id' => $order_id);
        $data = ecjia_touch_manager::make()->api(ecjia_touch_api::ORDER_AFFIRMRECEIVED)->data($params_order)
        ->send()->getBody();
        $data = json_decode($data,true);
        if (isset($_GET['from']) && $_GET['from'] == 'list') {
            $url = RC_Uri::url('user/order/order_list');
        } else {
            $url = RC_Uri::url('user/order/order_detail', array('order_id' => $order_id));
        }
        if ($data['status']['succeed']) {
            return ecjia_front::$controller->redirect($url);
        } else {
            return ecjia_front::$controller->showmessage($data['status']['error_desc'], ecjia::MSGSTAT_ERROR | ecjia::MSGTYPE_ALERT, array('pjaxurl' => $url));
        }
    }
}

// end