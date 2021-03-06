<?php
  
RC_Loader::load_app_class('platform_interface', 'platform', false);
class mp_zjd_init implements platform_interface {
    
    public function action() {
        $css_url = RC_Plugin::plugins_url('css/style.css', __FILE__);
    	$jq_url = RC_Plugin::plugins_url('js/jquery.js', __FILE__);
    	$tplpath = RC_Plugin::plugin_dir_path(__FILE__) . 'templates/zjd_index.dwt.php';
    	RC_Loader::load_app_class('platform_account', 'platform', false);
    	
    	ecjia_front::$controller->assign('jq_url',$jq_url);
    	ecjia_front::$controller->assign('css_url',$css_url);
    	
    	$img6= RC_Plugin::plugins_url('images/img-6.png',__FILE__);  
    	$img4= RC_Plugin::plugins_url('images/img-4.png',__FILE__);
    	$egg1= RC_Plugin::plugins_url('images/egg_1.png',__FILE__);
    	$egg2= RC_Plugin::plugins_url('images/egg_2.png',__FILE__);
    	ecjia_front::$controller->assign('img6',$img6);
    	ecjia_front::$controller->assign('img4',$img4);
    	ecjia_front::$controller->assign('egg1',$egg1);
    	ecjia_front::$controller->assign('egg2',$egg2);
    	
    	$platform_config_db = RC_Loader::load_app_model('platform_config_model','platform');
    	$wechat_prize_db = RC_Loader::load_app_model('wechat_prize_model','wechat');
    	$wechat_prize_view_db = RC_Loader::load_app_model('wechat_prize_viewmodel','wechat');
    	
    	$openid = trim($_GET['openid']);
    	$uuid = trim($_GET['uuid']);
    	$account = platform_account::make($uuid);
    	$wechat_id = $account->getAccountID();
    	$ext_config  = $platform_config_db->where(array('account_id' => $wechat_id,'ext_code'=>'mp_zjd'))->get_field('ext_config');
    	$config = array();
    	$config = unserialize($ext_config);
    	
    	foreach ($config as $k => $v) {
    		if ($v['name'] == 'starttime') {
    			$starttime = $v['value'];
    		}
    		if ($v['name'] == 'endtime') {
    			$endtime = $v['value'];
    		}
    		if ($v['name'] == 'prize_num') {
    			$prize_num = $v['value'];
    		}
    		if ($v['name'] == 'description') {
    			$description = $v['value'];
    		}
    		if ($v['name'] == 'list') {
    			$list = explode("\n",$v['value']);
    			foreach ($list as $k => $v){
    				$prize[] = explode(",",$v);
    			}
    		}
    	}
    	if (!empty($prize)) {
    		$num = count($prize);
    		if($num > 0){
    			foreach ($prize as $key => $val) {
    				if ($key == ($num - 1)) {
    					unset($prize[$key]);
    				}
    			}
    		}
    	}
    	
    	$starttime = strtotime($starttime);
    	$endtime   = strtotime($endtime);
    	$count = $wechat_prize_db->where('openid = "' . $openid . '"  and wechat_id = "' . $wechat_id . '"  and activity_type = "mp_zjd" and dateline between "' . $starttime . '" and "' . $endtime . '"')->count();
    	$prize_num = ($prize_num - $count) < 0 ? 0 : $prize_num - $count;
    	$list = $wechat_prize_view_db->where('p.wechat_id = "' . $wechat_id . '" and p.prize_type = 1  and p.activity_type = "mp_zjd" and dateline between "' . $starttime . '" and "' . $endtime . '"')->order('dateline desc')->limit(10)->select();
    	
    	ecjia_front::$controller->assign('form_action',RC_Uri::url('platform/plugin/show', array('handle' => 'mp_zjd/init_action', 'openid' => $openid, 'uuid' => $uuid)));
    	ecjia_front::$controller->assign('prize',$prize);
    	ecjia_front::$controller->assign('list',$list);
    	ecjia_front::$controller->assign('prize_num',$prize_num);
    	ecjia_front::$controller->assign('description',$description);
        ecjia_front::$controller->assign_lang();
        ecjia_front::$controller->display($tplpath);
	}
}

// end