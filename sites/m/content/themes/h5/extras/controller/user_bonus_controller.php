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
 * 红包模块控制器代码
 */
class user_bonus_controller {
    /**
     * 我的红包
     */
    public static function init() {
        ecjia_front::$controller->assign_title('我的红包');
        ecjia_front::$controller->display('user_bonus.dwt');
    }


    public static function async_allow_use() {
        $page = intval($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = intval($_GET['size']) > 0 ? intval($_GET['size']) : 10;
        $cur_date = RC_Time::gmtime();
        $status = 'allow_use';
        
        $bonus = ecjia_touch_manager::make()->api(ecjia_touch_api::USER_BONUS)->data(array('pagination' => array('page' => $page, 'count' => $limit), 'bonus_type' => $status))->send()->getBody();
        $bonus = json_decode($bonus,true);
        $token = ecjia_touch_manager::make()->api(ecjia_touch_api::SHOP_TOKEN)->run();
        ecjia_front::$controller->assign('bonus', $bonus['data']);
        
        $sayList = ecjia_front::$controller->fetch('user_bonus.dwt');
        $more = 0;
        if ($bonus['paginated']['more'] == 0) {
            $more = 1;
        }
        return ecjia_front::$controller->showmessage('success', ecjia::MSGSTAT_SUCCESS | ecjia::MSGTYPE_JSON, array('list' => $sayList,'page', 'is_last' => $more));
    }
    
    public static function async_is_used() {
        $page = intval($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = intval($_GET['size']) > 0 ? intval($_GET['size']) : 10;
        $cur_date = RC_Time::gmtime();

        $status = 'is_used';
        $bonus = ecjia_touch_manager::make()->api(ecjia_touch_api::USER_BONUS)->data(array('pagination' => array('page' => $page, 'count' => $limit), 'bonus_type' => $status))->send()->getBody();
        $bonus = json_decode($bonus,true);
        $token = ecjia_touch_manager::make()->api(ecjia_touch_api::SHOP_TOKEN)->run();
        ecjia_front::$controller->assign('bonus', $bonus['data']);

        $sayList = ecjia_front::$controller->fetch('user_bonus.dwt');
        $more = 0;
        if ($bonus['paginated']['more'] == 0) {
            $more = 1;
        }
        return ecjia_front::$controller->showmessage('success', ecjia::MSGSTAT_SUCCESS | ecjia::MSGTYPE_JSON, array('list' => $sayList,'page', 'is_last' => $more));
    }
    
    public static function async_expired() {
        $page = intval($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = intval($_GET['size']) > 0 ? intval($_GET['size']) : 10;
        $cur_date = RC_Time::gmtime();

        $status = 'expired';
        $bonus = ecjia_touch_manager::make()->api(ecjia_touch_api::USER_BONUS)->data(array('pagination' => array('page' => $page, 'count' => $limit), 'bonus_type' => $status))->send()->getBody();
        $bonus = json_decode($bonus,true);
        $token = ecjia_touch_manager::make()->api(ecjia_touch_api::SHOP_TOKEN)->run();
        ecjia_front::$controller->assign('bonus', $bonus['data']);
        
        $sayList = ecjia_front::$controller->fetch('user_bonus.dwt');
        $more = 0;
        if ($bonus['paginated']['more'] == 0) {
            $more = 1;
        }
        return ecjia_front::$controller->showmessage('success', ecjia::MSGSTAT_SUCCESS | ecjia::MSGTYPE_JSON, array('list' => $sayList,'page', 'is_last' => $more));
    }

    /**
     * 我的奖励
     */
    public static function my_reward() {
		$token = ecjia_touch_manager::make()->api(ecjia_touch_api::SHOP_TOKEN)->run();
    	$invite_reward = ecjia_touch_manager::make()->api(ecjia_touch_api::INVITE_REWARD)->data(array('token' => $token['access_token']))->run();
        $intive_total = $invite_reward[invite_total];
        
        ecjia_front::$controller->assign_title('我的奖励');
        ecjia_front::$controller->assign('intive_total', $intive_total);
        ecjia_front::$controller->display('user_my_reward.dwt');
    }
    
    /**
     * 奖励明细
     */
    public static function reward_detail() {
        $type = !empty($_GET['type']) ? $_GET['type'] : '';
        
		$token = ecjia_touch_manager::make()->api(ecjia_touch_api::SHOP_TOKEN)->run();
    	$invite_reward = ecjia_touch_manager::make()->api(ecjia_touch_api::INVITE_REWARD)->data(array('token' => $token['access_token']))->run();
    	ecjia_front::$controller->assign('month', $invite_reward['invite_record']);
    	
    	$max_key = array_keys($invite_reward['invite_record'], max($invite_reward['invite_record']));
    	$max_month = $invite_reward['invite_record'][$max_key[0]]['invite_data'];

    	$arr = array(
    	    'token'        => $token['access_token'], 
    	    'pagination'   => array('page' => 1, 'count' => 10), 
    	    'date'         => $max_month
    	);
    	$invite_record = ecjia_touch_manager::make()->api(ecjia_touch_api::INVITE_RECORD)->data($arr)->send()->getBody();
    	$data = json_decode($invite_record, true);
    	
    	ecjia_front::$controller->assign('data', $data['data']);
    	ecjia_front::$controller->assign('is_last', $data['paginated']['more']);
    	ecjia_front::$controller->assign('max_month', $max_month);
    	ecjia_front::$controller->assign_title('奖励明细');
        ecjia_front::$controller->display('user_reward_detail.dwt');
    }
    /**
     * 奖励明细异步加载
     */
    public static function async_reward_detail() {
        $page = intval($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = intval($_GET['size']) > 0 ? intval($_GET['size']) : 10;
        
        $token = ecjia_touch_manager::make()->api(ecjia_touch_api::SHOP_TOKEN)->run();
        $arr = array('token' => $token['access_token']);
        $arr['date'] = $_GET['date'];
        $arr['pagination'] = array('page' => $page, 'count' => $limit);
        
        $data = ecjia_touch_manager::make()->api(ecjia_touch_api::INVITE_RECORD)->data($arr)->send()->getBody();
        $data = json_decode($data, true);
        
        if (!empty($data['data'])) {
            ecjia_front::$controller->assign('data', $data['data']);
            $sayList = ecjia_front::$controller->fetch('user_reward_detail.dwt');
        }
        $res = array();
        if ($data['paginated']['more'] == 0) {
            $res['is_last'] = 1;
        } else {
            $res['data_toggle'] = 'asynclist';
            $res['url'] = RC_Uri::url('user/bonus/async_reward_detail', array('date' => $arr['date']));
        }
        return ecjia_front::$controller->showmessage('', ecjia::MSGSTAT_SUCCESS | ecjia::MSGTYPE_JSON, array('list' => $sayList, 'is_last' => $res['is_last'], 'data' => $res));
    }
    /**
     * 赚积分
     */
    public static function get_integral() {
        ecjia_front::$controller->assign_title('赚积分');
        ecjia_front::$controller->display('user_get_integral.dwt');
    }
}

// end