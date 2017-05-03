<?php
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * ECJIA用户管理
 */
class admin_subscribe extends ecjia_admin {
	private $wu_viewdb;
	private $wechat_user_db;
	private $wechat_user_tag;
	private $wechat_tag;
	private $wechat_user_group;
	private $custom_message_viewdb;
	private $db_platform_account;
	
	public function __construct() {
		parent::__construct();
		RC_Lang::load('wechat');
		RC_Loader::load_app_func('global');
		assign_adminlog_content();
		
		$this->wu_viewdb = RC_Loader::load_app_model('wechat_user_viewmodel');
		$this->wechat_user_db = RC_Loader::load_app_model('wechat_user_model');
		$this->wechat_user_tag = RC_Loader::load_app_model('wechat_user_tag_model');
		$this->wechat_tag = RC_Loader::load_app_model('wechat_tag_model');
		$this->wechat_user_group = RC_Loader::load_app_model('wechat_user_group_model');
		$this->custom_message_viewdb = RC_Loader::load_app_model('wechat_custom_message_viewmodel');
		$this->db_platform_account = RC_Loader::load_app_model('platform_account_model', 'platform');
		
		RC_Loader::load_app_class('platform_account', 'platform', false);
		RC_Loader::load_app_class('wechat_method', 'wechat', false);
		
		RC_Script::enqueue_script('jquery-validate');
		RC_Script::enqueue_script('jquery-form');
		RC_Script::enqueue_script('smoke');
		RC_Style::enqueue_style('chosen');
		RC_Style::enqueue_style('uniform-aristo');
		RC_Script::enqueue_script('jquery-uniform');
		RC_Script::enqueue_script('jquery-chosen');
		RC_Style::enqueue_style('bootstrap-responsive');
		
		RC_Script::enqueue_script('admin_subscribe', RC_App::apps_url('statics/js/admin_subscribe.js', __FILE__));
		RC_Style::enqueue_style('admin_subscribe', RC_App::apps_url('statics/css/admin_subscribe.css', __FILE__));
		
		RC_Script::localize_script('admin_subscribe', 'js_lang', RC_Lang::get('wechat::wechat.js_lang'));
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('wechat::wechat.subscribe_manage'), RC_Uri::url('wechat/admin_subscribe/init')));
	}

	public function init() {
		$this->admin_priv('wechat_subscribe_manage');

		ecjia_screen::get_current_screen()->remove_last_nav_here();
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('wechat::wechat.subscribe_manage')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id'		=> 'overview',
			'title'		=> RC_Lang::get('wechat::wechat.overview'),
			'content'	=> '<p>' . RC_Lang::get('wechat::wechat.subscribe_manage_content') . '</p>'
		));
		ecjia_screen::get_current_screen()->set_help_sidebar(
			'<p><strong>' . RC_Lang::get('wechat::wechat.more_info') . '</strong></p>' .
			'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia公众平台:用户管理#.E7.94.A8.E6.88.B7.E7.AE.A1.E7.90.86" target="_blank">'. RC_Lang::get('wechat::wechat.subscribe_manage_help') .'</a>') . '</p>'
		);
		
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		
		$this->assign('ur_here', RC_Lang::get('wechat::wechat.subscribe_manage'));
		$this->assign('form_action', RC_Uri::url('wechat/admin_subscribe/init'));
		$this->assign('action', RC_Uri::url('wechat/admin_subscribe/subscribe_move'));
		$this->assign('label_action', RC_Uri::url('wechat/admin_subscribe/batch'));
		$this->assign('get_checked', RC_Uri::url('wechat/admin_subscribe/get_checked_tag'));
		
		if (is_ecjia_error($wechat_id)) {
			$this->assign('errormsg', RC_Lang::get('wechat::wechat.add_platform_first'));
		} else {
			$this->assign('warn', 'warn');
			
			//微信id、type、关键字
			$where = "u.wechat_id = $wechat_id";
			$type     = isset($_GET['type'])     ? $_GET['type']           : 'all';
			$keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
			
			//用户标签列表
			$tag_arr['all'] = $this->wu_viewdb->join(null)->where(array('wechat_id' => $wechat_id, 'subscribe' => 1, 'group_id' => array('neq' => 1)))->count();
			$tag_arr['item'] = $this->wechat_tag->field('id, tag_id, name, count')->where(array('wechat_id' => $wechat_id))->order(array('id' => 'desc'))->select();
			$this->assign('tag_arr', $tag_arr);
			
			//关键字搜索
			if (!empty($keywords)) {
				$where .= ' and (u.nickname like "%' . $keywords . '%" or u.province like "%' . $keywords . '%" or u.city like "%' . $keywords . '%")';
			}

			//全部用户
			if ($type == 'all') {
				$where .= " and u.subscribe = 1 and u.group_id != 1";
			//标签用户
			} elseif ($type == 'subscribed') {
				$tag_id = isset($_GET['tag_id']) ? $_GET['tag_id'] : '';
				if (!empty($tag_id)) {
					$user_list = $this->wechat_user_tag->where(array('tagid' => $tag_id))->get_field('userid', true);
					if (empty($user_list)) {
						$user_list = 0;
					}
					$where .= ' and u.group_id != 1 and u.uid'.db_create_in($user_list);
				}
			//黑名单
			} elseif ($type == 'blacklist') {
				$where .= ' and u.group_id = 1';
			//取消关注
			} elseif ($type == 'unsubscribe') {
				$where .= " and u.subscribe = 0 and u.group_id = 0";
			}
			//用户列表
			$total = $this->wu_viewdb->join(null)->where($where)->count();
			$page = new ecjia_page($total, 10, 5);
			$list = $this->wu_viewdb->join(array('users'))->field('u.*, user_name')->where($where)->order(array('u.subscribe_time' => 'desc'))->limit($page->limit())->select();
			
			if (!empty($list)) {
				foreach ($list as $k => $v) {
					//假如不是黑名单
					if ($v['group_id'] != 1) {
						$tag_list = $this->wechat_user_tag->where(array('userid' => $v['uid']))->get_field('tagid', true);
						$name_list = $this->wechat_tag->where(array('tag_id' => $tag_list, 'wechat_id' => $wechat_id))->order(array('tag_id' => 'desc'))->get_field('name', true);
						if (!empty($name_list)) {
							$list[$k]['tag_name'] = implode('，', $name_list);
						}
					}
				}
			}
			$arr = array('item' => $list, 'page' => $page->show(5), 'desc' => $page->page_desc());
			$this->assign('list', $arr);

			if (isset($_GET['action']) && $_GET['action'] == 'get_list') {
				//无unionid给提示
				if (!empty($list)) {
					if (empty($list[0]['unionid'])) {
						$unionid = 1;
						$this->assign('unionid', $unionid);
					}
				}
			}
			
			//取消关注用户数量
			$where = array('wechat_id' => $wechat_id, 'subscribe' => 0, 'group_id' => 0);
			$num = $this->wechat_user_db->where($where)->count();
			$this->assign('num', $num);
			
			//获取公众号类型 0未认证 1订阅号 2服务号 3认证服务号 4企业号
			$types = $this->db_platform_account->where(array('id' => $wechat_id))->get_field('type');
			$this->assign('type', $types);
			$this->assign('type_error', sprintf(RC_Lang::get('wechat::wechat.notice_certification_info'), RC_Lang::get('wechat::wechat.wechat_type.'.$types)));
		}
	
		$this->assign_lang();
		$this->display('wechat_subscribe_list.dwt');
	}
	
	public function edit_tag() {
		$uuid = platform_account::getCurrentUUID('wechat');
		$wechat = wechat_method::wechat_instance($uuid);
		
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		
		if (is_ecjia_error($wechat_id)) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.add_platform_first'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		
		$id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
		$name = !empty($_POST['new_tag']) ? $_POST['new_tag'] : '';
		if (empty($name)) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.tag_name_required'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		if (!empty($id)) {
			$this->admin_priv('wechat_subscribe_update', ecjia::MSGTYPE_JSON);
			
			$data = array('name' => $name);
			$is_only = $this->wechat_tag->where(array('id' => array('neq' => $id), 'name' => $name, 'wechat_id' => $wechat_id))->count();
			if ($is_only != 0 ) {
				return $this->showmessage(RC_Lang::get('wechat::wechat.tag_name_exist'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
			
			$tag_id = $this->wechat_tag->where(array('id' => $id))->get_field('tag_id');
			//微信端更新
			$rs = $wechat->setTag($tag_id, $name);
			if (RC_Error::is_error($rs)) {
				return $this->showmessage(wechat_method::wechat_error($rs->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
			
			//本地更新
			$update = $this->wechat_tag->where(array('id' => $id, 'wechat_id' => $wechat_id))->update($data);
			
			//记录日志
			ecjia_admin::admin_log($name, 'edit', 'users_tag');
			if ($update) {
				return $this->showmessage(RC_Lang::get('wechat::wechat.edit_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('wechat/admin_subscribe/init')));
			} else {
				return $this->showmessage(RC_Lang::get('wechat::wechat.edit_failed'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		} else {
			$this->admin_priv('wechat_subscribe_add', ecjia::MSGTYPE_JSON);
			
			$count = $this->wechat_tag->where(array('wechat_id' => $wechat_id))->count();
			if ($count == 100) {
				return $this->showmessage(RC_Lang::get('wechat::wechat.up_tag_info'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
			
			$is_only = $this->wechat_tag->where(array('name' => $name, 'wechat_id' => $wechat_id))->count();
			if ($is_only != 0 ) {
				return $this->showmessage(RC_Lang::get('wechat::wechat.tag_name_exist'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
			
			//微信端添加
			$rs = $wechat->addTag($name);
			if (RC_Error::is_error($rs)) {
				return $this->showmessage(wechat_method::wechat_error($rs->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
			$tag_id = $rs['tag']['id'];
			
			//本地添加
			$data = array('name' => $name, 'wechat_id' => $wechat_id, 'tag_id' => $tag_id);
			$id = $this->wechat_tag->insert($data);
			//记录日志
			ecjia_admin::admin_log($name, 'add', 'users_tag');
			if ($id) {
				return $this->showmessage(RC_Lang::get('wechat::wechat.add_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('wechat/admin_subscribe/init')));
			} else {
				return $this->showmessage(RC_Lang::get('wechat::wechat.add_failed'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		}
	}
	
	/**
	 * 删除标签
	 */
	public function remove() {
		$this->admin_priv('wechat_subscribe_delete', ecjia::MSGTYPE_JSON);
		
		$tag_id = !empty($_GET['tag_id']) ? intval($_GET['tag_id']) : 0;
		$id     = !empty($_GET['id'])     ? intval($_GET['id'])     : 0;
		
		$uuid = platform_account::getCurrentUUID('wechat');
		$wechat = wechat_method::wechat_instance($uuid);
		
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		
		if (is_ecjia_error($wechat_id)) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.add_platform_first'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		//微信端删除
		$rs = $wechat->deleteTag($tag_id);
		if (RC_Error::is_error($rs)) {
			return $this->showmessage(wechat_method::wechat_error($rs->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		
		//本地删除
		$name = $this->wechat_tag->where(array('id' => $id))->get_field('name');
		$delete = $this->wechat_tag->where(array('id' => $id, 'tag_id' => $tag_id))->delete();
		
		//记录日志
		ecjia_admin::admin_log($name, 'remove', 'users_tag');
		$this->wechat_user_db->where(array('group_id' => $tag_id))->update(array('group_id' => 0));
		
		if ($delete){
			return $this->showmessage(RC_Lang::get('wechat::wechat.remove_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS);
		} else {
			return $this->showmessage(RC_Lang::get('wechat::wechat.remove_failed'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
	}

	/**
	 * 获取全部标签
	 */
	public function get_usertag() {
		$this->admin_priv('wechat_subscribe_manage', ecjia::MSGTYPE_JSON);
		
		$result = $this->get_user_tags();
		if ($result === true) {
			//记录日志
			ecjia_admin::admin_log(RC_Lang::get('wechat::wechat.get_user_tag'), 'setup', 'users_tag');
			return $this->showmessage(RC_Lang::get('wechat::wechat.get_tag_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('wechat/admin_subscribe/init')));
		}
	}
	
	/**
	 * 获取用户信息
	 */
	public function get_userinfo() {
		$this->admin_priv('wechat_subscribe_manage', ecjia::MSGTYPE_JSON);
		
		$uuid = platform_account::getCurrentUUID('wechat');
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		$wechat = wechat_method::wechat_instance($uuid);

		if (is_ecjia_error($wechat_id)) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.add_platform_first'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		//读取上次获取用户位置
		$p = RC_Cache::app_cache_get('wechat_user_position_'.$wechat_id, 'wechat');
		
		if ($p == false) {
			$p = !empty($_GET['p']) ? intval($_GET['p']) : 0;	
		}
		//删除缓存
		if (empty($p)) {
			RC_Cache::app_cache_delete('wechat_user_list_'.$wechat_id, 'wechat');
		}
		
		//读取缓存
		$wechat_user_list =  RC_Cache::app_cache_get('wechat_user_list_'.$wechat_id, 'wechat');
		if ($wechat_user_list == false) {
			$wechat_user = $wechat->getUserList();
			if (RC_Error::is_error($wechat_user)) {
				return $this->showmessage(wechat_method::wechat_error($wechat_user->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
			
			if ($wechat_user['total'] <= 10000) {
				$wechat_user_list = $wechat_user['data']['openid'];
			} else {
				$num = ceil($wechat_user['total'] / 10000);
				for ($i = 1; $i < $num; $i ++) {
					$wechat_user1 = $wechat->getUserList($wechat_user['next_openid']);
					if (RC_Error::is_error($wechat_user1)) {
						return $this->showmessage(wechat_method::wechat_error($wechat_user1->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
					}
					$wechat_user_list = array_merge($wechat_user['data']['openid'], $wechat_user1['data']['openid']);
				}
			}
			//设置缓存
			RC_Cache::app_cache_set('wechat_user_list_'.$wechat_id, $wechat_user_list, 'wechat');
		}
		
		$user_list = $this->wechat_user_db->where(array('wechat_id' => $wechat_id))->get_field('openid', true);
		if (empty($user_list)) {
			$user_list = array();
		}
		
		//比较微信端获取的用户列表 与 本地数据表用户列表
		if (empty($_GET['p'])) {
			foreach ($user_list as $v) {
				if (!in_array($v, $wechat_user_list)) {
					$unsubscribe_list[] = $v;
				}
			}
			//更新取消关注用户
			if (!empty($unsubscribe_list)) {
				$where = array(
					'wechat_id' => $wechat_id,
					'openid' . db_create_in($unsubscribe_list)
				);
				$this->wechat_user_db->where($where)->update(array('subscribe' => 0));
				
				//删除取消关注用户的标签
				$uid_list = $this->wechat_user_db->where($where)->get_field('uid', true);
				$this->wechat_user_tag->where(array('userid' => $uid_list))->delete();
			}
		}
		
		$arr1 = $arr2 = array();
		$list = array_slice($wechat_user_list, $p, 100);
		
		$total = count($wechat_user_list);
		$counts = count($list);
		
		$p += $counts;
		$where = '';
		if (!empty($list)) {
			foreach ($list as $k => $vs) {
				//不在表中为新关注用户、添加用户信息
				if (!in_array($vs, $user_list)) {
					$arr1[] = $vs;
					
				} else {
					//在表中为原来关注用户、更新用户信息
					$arr2[] = $vs;
				}
			}
		}
		//添加
		if (!empty($arr1)) {
			$info2 = $wechat->getUserInfoBatch($arr1);
			if (RC_Error::is_error($info2)) {
				return $this->showmessage(wechat_method::wechat_error($info2->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
			foreach ($info2['user_info_list'] as $key => $v) {
				$info2['user_info_list'][$key]['wechat_id'] = $wechat_id;
				$uid = $this->wechat_user_db->insert($info2['user_info_list'][$key]);
				if (!empty($v['tagid_list'])) {
					foreach ($v['tagid_list'] as $val) {
						if (!empty($val)) {
							$this->wechat_user_tag->insert(array('userid' => $uid, 'tagid' => $val));
						}
					}
				}
			}
		}
		//更新
		if (!empty($arr2)) {
			$info3 = $wechat->getUserInfoBatch($arr2);
			if (RC_Error::is_error($info3)) {
				return $this->showmessage(wechat_method::wechat_error($info3->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
			foreach ($info3['user_info_list'] as $key => $v) {
				$info3['user_info_list'][$key]['subscribe'] = 1;
				$where['wechat_id'] = $wechat_id;
				$where['openid'] = $v['openid'];
				$this->wechat_user_db->where($where)->update($info3['user_info_list'][$key]);
				
				$uid = $this->wechat_user_db->where($where)->get_field('uid');
				if (!empty($v['tagid_list'])) {
					$this->wechat_user_tag->where(array('userid' => $uid))->delete();
					foreach ($v['tagid_list'] as $val) {
						if (!empty($val)) {
							$this->wechat_user_tag->insert(array('userid' => $uid, 'tagid' => $val));
						}
					}
				}
			}
		}
		
		if ($p < $total) {
			RC_Cache::app_cache_set('wechat_user_position_'.$wechat_id, $p, 'wechat');
			return $this->showmessage(sprintf(RC_Lang::get('wechat::wechat.get_user_already'), $p), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('url' => RC_Uri::url("wechat/admin_subscribe/get_userinfo"), 'notice' => 1, 'p' => $p));
		} else {
			RC_Cache::app_cache_delete('wechat_user_position_'.$wechat_id, 'wechat');
			RC_Cache::app_cache_delete('wechat_user_list_'.$wechat_id, 'wechat');
			
			ecjia_admin::admin_log(RC_Lang::get('wechat::wechat.get_user_info'), 'setup', 'users_info');
			return $this->showmessage(RC_Lang::get('wechat::wechat.get_userinfo_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('wechat/admin_subscribe/init', array('action' => 'get_list'))));
		}
	}
	
	//用户消息记录	
	public function subscribe_message() {
		$this->admin_priv('wechat_subscribe_message_manage');
		
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		
		$page = !empty($_GET['page']) ? intval($_GET['page']) : 1;
		$this->assign('ur_here', RC_Lang::get('wechat::wechat.user_message_record'));
		
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('wechat::wechat.user_message_record')));
		$this->assign('action_link', array('text' => RC_Lang::get('wechat::wechat.subscribe_manage'), 'href'=> RC_Uri::url('wechat/admin_subscribe/init', array('page' => $page))));
		
		if (is_ecjia_error($wechat_id)) {
			$this->assign('errormsg', RC_Lang::get('wechat::wechat.add_platform_first'));
		} else {
		 	$this->assign('warn', 'warn');
		 	
		 	//获取公众号类型 0未认证 1订阅号 2服务号 3认证服务号 4企业号
		 	$type = $this->db_platform_account->where(array('id' => $wechat_id))->get_field('type');
		 	$this->assign('type', $type);
		 	$this->assign('type_error', sprintf(RC_Lang::get('wechat::wechat.notice_certification_info'), RC_Lang::get('wechat::wechat.wechat_type.'.$type)));
		 	
		 	$tag_arr['item'] = $this->wechat_tag
                        		 	->field('id, tag_id, name, count')
                        		 	->where(array('wechat_id' => $wechat_id))
                        		 	->order(array('id' => 'desc', 'sort' => 'desc'))
                        		 	->select();
		 	$this->assign('tag_arr', $tag_arr);
		 	
		 	$uid = !empty($_GET['uid']) ? intval($_GET['uid']) : 0;
		 	$this->assign('chat_action', RC_Uri::url('wechat/admin_subscribe/send_message'));
		 	$this->assign('last_action', RC_Uri::url('wechat/admin_subscribe/read_message'));
		 	$this->assign('label_action', RC_Uri::url('wechat/admin_subscribe/batch'));
		 	$this->assign('get_checked', RC_Uri::url('wechat/admin_subscribe/get_checked_tag'));
		 	
		 	if (empty($uid)) {
		 		return $this->showmessage(RC_Lang::get('wechat::wechat.pls_select_user'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		 	}
		 	$info = $this->wu_viewdb->join(array('users'))->field('u.*, us.user_name')->find(array('u.uid' => $uid, 'u.wechat_id' => $wechat_id));
		 	if (!empty($info)) {
		 		if ($info['subscribe_time']) {
		 			$info['subscribe_time'] = RC_Time::local_date(ecjia::config('time_format'), $info['subscribe_time']-8*3600);
		 		}
		 		$tag_list = $this->wechat_user_tag->where(array('userid' => $info['uid']))->get_field('tagid', true);
		 		$name_list = $this->wechat_tag
                		 		->where(array('tag_id' => $tag_list, 'wechat_id' => $wechat_id))
                		 		->order(array('tag_id' => 'desc'))
                		 		->get_field('name', true);
		 		if (!empty($name_list)) {
		 			$info['tag_name'] = implode('，', $name_list);
		 		}
		 	}
		 	$this->assign('info', $info);
		 	$message = $this->get_message_list();
		 	$this->assign('message', $message);
		 	
		 	//最后发送时间
		 	$last_send_time = $this->custom_message_viewdb
                        		 	->join(null)
                        		 	->where(array('uid' => $uid, 'iswechat' => 0))
                        		 	->order(array('id' => 'desc'))
                        		 	->limit(1)
                        		 	->get_field('send_time');
		 	$time = RC_Time::gmtime();
		 	if ($time - $last_send_time > 48*3600) {
		 		$this->assign('disabled', '1');
		 	}
		}
		$this->assign_lang();
		$this->display('wechat_subscribe_message.dwt');
	}
	
	//获取信息
	public function read_message() {
		$this->admin_priv('wechat_subscribe_message_manage', ecjia::MSGTYPE_JSON);
		
		$list = $this->get_message_list();
		$message = count($list['item']) < 10 ? RC_Lang::get('wechat::wechat.no_more_message') : RC_Lang::get('wechat::wechat.searched');
		if (!empty($list['item'])) {
			return $this->showmessage($message, ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('msg_list' => $list['item'], 'last_id' => $list['last_id']));
		} else {
			return $this->showmessage($message, ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
	}
	
	//发送信息
	public function send_message() {
		$this->admin_priv('wechat_subscribe_message_add', ecjia::MSGTYPE_JSON);
		
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		$uuid = platform_account::getCurrentUUID('wechat');
		$wechat = wechat_method::wechat_instance($uuid);
		
		if (is_ecjia_error($wechat_id)) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.add_platform_first'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		
		$openid = !empty($_POST['openid']) ? $_POST['openid'] : '';
		$data['msg'] = !empty($_POST['message']) ? $_POST['message'] : '';
		$data['uid'] = !empty($_POST['uid']) ? intval($_POST['uid']) : 0;
		
		if (empty($openid)) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.pls_select_user'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		
		if (empty($data['msg'])) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.message_content_required'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		
		$data['send_time'] = RC_Time::gmtime();
		$data['iswechat'] = 1;
			
		// 微信端发送消息
		$msg = array(
			'touser' 	=> $openid,
			'msgtype' 	=> 'text',
			'text' 		=> array(
				'content' => $data['msg']
			)
		);
		
		$rs = $wechat->sendCustomMessage($msg);
		if (RC_Error::is_error($rs)) {
			return $this->showmessage(wechat_method::wechat_error($rs->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		// 添加数据
		$message_id = $this->custom_message_viewdb->join(null)->insert($data);
		ecjia_admin::admin_log($data['msg'], 'send', 'subscribe_message');
		if ($message_id) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.send_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('send_time' => RC_Time::local_date(ecjia::config('time_format'), RC_Time::gmtime())));
		} else {
			return $this->showmessage(RC_Lang::get('wechat::wechat.send_failed'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
	}
	
	public function edit_remark() {
		$this->admin_priv('wechat_subscribe_update', ecjia::MSGTYPE_JSON);
		
		$uuid = platform_account::getCurrentUUID('wechat');
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		$wechat = wechat_method::wechat_instance($uuid);
		
		if (is_ecjia_error($wechat_id)) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.add_platform_first'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		// 数据更新
		$remark	= !empty($_POST['remark'])	? trim($_POST['remark']) : '';
		$openid = !empty($_POST['openid']) 	? trim($_POST['openid']) : '';
		$page 	= !empty($_POST['page']) 	? intval($_POST['page']) : 1;
		$uid 	= !empty($_POST['uid']) 	? intval($_POST['uid'])  : 0;
		
		if (strlen($remark) > 30) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.up_remark_count'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		$info = $this->wechat_user_db->find(array('openid' => $openid));
		//微信端更新
		$rs = $wechat->setUserRemark($openid, $remark);
		if (RC_Error::is_error($rs)) {
			return $this->showmessage(wechat_method::wechat_error($rs->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		$data = array('remark' => $remark);
		$update = $this->wechat_user_db->where(array('openid' => $openid, 'wechat_id' => $wechat_id))->update($data);
		
		ecjia_admin::admin_log(sprintf(RC_Lang::get('wechat::wechat.edit_remark_to'), $info['nickname'], $remark), 'edit', 'users_info');
		if ($update) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.edit_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('wechat/admin_subscribe/subscribe_message', array('uid' => $uid, 'page' => $page))));
		} else {
			return $this->showmessage(RC_Lang::get('wechat::wechat.edit_failed'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
	}
	
	//添加/移出黑名单
	public function backlist() {
		$this->admin_priv('wechat_subscribe_update', ecjia::MSGTYPE_JSON);
		
		$uuid = platform_account::getCurrentUUID('wechat');
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		
		if (is_ecjia_error($wechat_id)) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.add_platform_first'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		$wechat = wechat_method::wechat_instance($uuid);
		$uid 	= !empty($_GET['uid']) 		? intval($_GET['uid']) 		: 0;
		$type 	= !empty($_GET['type']) 	? trim($_GET['type']) 		: '';
		$page 	= !empty($_GET['page']) 	? intval($_GET['page']) 	: 1;
		$openid = !empty($_GET['openid']) 	? trim($_GET['openid']) 	: '';
		
		if ($type == 'remove_out') {
			$data['group_id']  = 0;
			$data['subscribe'] = 1;
			$sn                = RC_Lang::get('wechat::wechat.remove_blacklist');
			$success_msg       = RC_Lang::get('wechat::wechat.remove_blacklist_success');
			$error_msg         = RC_Lang::get('wechat::wechat.remove_blacklist_error');
		} else {
			$data['group_id']  = 1;
			$data['subscribe'] = 0;
			$sn                = RC_Lang::get('wechat::wechat.add_blacklist');
			$success_msg       = RC_Lang::get('wechat::wechat.add_blacklist_success');
			$error_msg         = RC_Lang::get('wechat::wechat.add_blacklist_error');
		}
		
		//微信端更新
		$rs = $wechat->setUserGroup($openid, $data['group_id']);
		if (RC_Error::is_error($rs)) {
			return $this->showmessage(wechat_method::wechat_error($rs->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		ecjia_admin::admin_log($sn, 'setup', 'users_info');
		$update = $this->wechat_user_db->where(array('uid' => $uid))->update($data);
		
		if ($update) {
			$this->get_user_tags();
			return $this->showmessage($success_msg, ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('wechat/admin_subscribe/subscribe_message', array('uid' => $uid, 'page' => $page))));
		} else {
			return $this->showmessage($error_msg, ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
	}
	
	//获取消息列表
	public function get_message_list() {
		$custom_message_viewdb = RC_Loader::load_app_model('wechat_custom_message_viewmodel');
		
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		$platform_name = $this->db_platform_account->where(array('id' => $wechat_id))->get_field('name');
		
		$uid     = !empty($_GET['uid'])     ? intval($_GET['uid'])     : 0;
		$last_id = !empty($_GET['last_id']) ? intval($_GET['last_id']) : 0;
		$chat_id = !empty($_GET['chat_id']) ? intval($_GET['chat_id']) : 0;
		
		if (!empty($last_id)) {
			$where =  "m.uid = '".$chat_id."' AND (m.iswechat = 0 OR m.iswechat = 1) AND m.id<".$last_id;
		} else {
			$where =  "m.uid = '".$uid."' AND (m.iswechat = 0 OR m.iswechat = 1)";
		}
		$count = $custom_message_viewdb->where($where)->count();
		$page = new ecjia_page($count, 10, 5);
		$limit = $page->limit();

		$list = $custom_message_viewdb->join('wechat_user')
                                		->field('m.*, wu.nickname')
                                		->where($where)
                                		->order(array('m.id' => 'desc'))
                                		->limit($limit)
                                		->select();

		if (!empty($list)) {
			foreach ($list as $key => $val) {
				$list[$key]['send_time'] = RC_Time::local_date(ecjia::config('time_format'), $val['send_time']);
				if (!empty($val['iswechat'])) {
					$list[$key]['nickname'] = $platform_name;
				}
			}
			$end_list     = end($list);
			$reverse_list = array_reverse($list);
		} else {
			$end_list     = null;
			$reverse_list = null;
		}
		return array('item' => $reverse_list, 'page' => $page->show(5), 'desc' => $page->page_desc(), 'last_id' => $end_list['id']);
	}
	
	//批量操作
	public function batch() {
		$uuid = platform_account::getCurrentUUID('wechat');
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		
		if (is_ecjia_error($wechat_id)) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.add_platform_first'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		$wechat = wechat_method::wechat_instance($uuid);
		$action = !empty($_GET['action']) 	? $_GET['action'] 	: '';
		$uid 	= !empty($_POST['uid']) 	? $_POST['uid'] 	: '';
		$openid = !empty($_POST['openid']) 	? $_POST['openid'] 	: '';
		$tag_id = !empty($_POST['tag_id']) 	? $_POST['tag_id'] 	: '';

		if (count($tag_id) > 3) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.up_tag_count'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		$openid_list = explode(',', $openid);
		$tag = array();
		$openids_no_tag = $openids_tag = array();

		foreach ($openid_list as $k => $v) {
			$tag = $this->wu_viewdb->join(array('wechat_user', 'wechat_user_tag'))->where(array('u.openid' => $v, 'u.wechat_id' => $wechat_id))->field('ut.tagid, u.uid, u.openid')->select();
			foreach ($tag as $key => $val) {
				if (empty($val['tagid'])) {
					//没有标签的用户
					$openids_no_tag['openid'][] = $val['openid'];
					$openids_no_tag['uid'][]	= $val['uid'];
				} else {
					//有标签的用户
					$openids_tag[$val['uid']][] = array('tagid' => $val['tagid'], 'openid' => $val['openid']);
				}
			}
		}

		if (!empty($openids_no_tag)) {
			//添加用户标签
			if (!empty($tag_id)) {
				foreach ($tag_id as $v) {
					$rs = $wechat->setBatchTag($openids_no_tag['openid'], $v);
					if (RC_Error::is_error($rs)) {
						return $this->showmessage(wechat_method::wechat_error($rs->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
					}
					foreach ($openids_no_tag['uid'] as $val) {
						$this->wechat_user_tag->insert(array('userid' => $val, 'tagid' => $v));
					}
				}
			}
		}
		
		//取消用户标签
		if (!empty($openids_tag)) {
			foreach ($openids_tag as $k => $v) {
				foreach ($v as $val) {
					$rs = $wechat->setBatchunTag($val['openid'], $val['tagid']);
					if (RC_Error::is_error($rs)) {
						return $this->showmessage(wechat_method::wechat_error($rs->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
					}
				}
				$this->wechat_user_tag->where(array('userid' => $k))->delete();
				$new_uid[] = $k;
				$new_openid[] = $val['openid'];
			}
			
			if (!empty($new_openid)) {
				$openid_unique = array_unique($new_openid);
			}
			if (!empty($tag_id)) {
				foreach ($tag_id as $v) {
					$rs = $wechat->setBatchTag($openid_unique, $v);
					if (RC_Error::is_error($rs)) {
						return $this->showmessage(wechat_method::wechat_error($rs->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
					}
					foreach ($new_uid as $val) {
						$this->wechat_user_tag->insert(array('userid' => $val, 'tagid' => $v));
					}
				}
			}
		}
		$this->get_user_tags();
		if ($action == 'set_label') {
			$url = RC_Uri::url('wechat/admin_subscribe/init', array('type' => 'all'));
		} elseif ($action == 'set_user_label') {
			$url = RC_Uri::url('wechat/admin_subscribe/subscribe_message', array('uid' => $uid));
		}
		return $this->showmessage(RC_Lang::get('wechat::wechat.edit_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => $url));
	}
	
	//获取选择用户的标签
	public function get_checked_tag() {
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		
		$uid = !empty($_POST['uid']) ? intval($_POST['uid']) : '';
		$tag_arr = $this->wechat_tag->field('id, tag_id, name, count')->where(array('wechat_id' => $wechat_id))->order(array('id' => 'desc', 'sort' => 'desc'))->select();
		$user_tag_list = array();
		if (!empty($uid)) {
			$user_tag_list = $this->wechat_user_tag->where(array('userid' => $uid))->get_field('tagid', true);
			if (empty($user_tag_list)) {
				$user_tag_list = array();
			}
		}
		foreach ($tag_arr as $k => $v) {
			if (in_array($v['tag_id'], $user_tag_list)) {
				$tag_arr[$k]['checked'] = 1;
			}
			if ($v['tag_id'] == 1) {
				unset($tag_arr[$k]);
			}
		}
		return $this->showmessage('', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('content' => $tag_arr));
	}
	
	//获取用户标签
	private function get_user_tags() {
		$uuid = platform_account::getCurrentUUID('wechat');
		$platform_account = platform_account::make(platform_account::getCurrentUUID('wechat'));
		$wechat_id = $platform_account->getAccountID();
		
		if (is_ecjia_error($wechat_id)) {
			return $this->showmessage(RC_Lang::get('wechat::wechat.add_platform_first'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		$wechat = wechat_method::wechat_instance($uuid);
		
		$list = $wechat->getTags();
		if (RC_Error::is_error($list)) {
			return $this->showmessage(wechat_method::wechat_error($list->get_error_code()), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		if (!empty($list['tags'])) {
			$where['wechat_id'] = $wechat_id;
			$this->wechat_tag->where($where)->delete();
			foreach ($list['tags'] as $key => $val) {
				$data['wechat_id']  = $wechat_id;
				$data['tag_id']     = $val['id'];
				$data['name']       = $val['name'] == RC_Lang::get('wechat::wechat.star_group') ? RC_Lang::get('wechat::wechat.star_user') : $val['name'];
				$data['count']      = $val['count'];
				$this->wechat_tag->insert($data);
			}
		}
		return true;
	}
}

//end