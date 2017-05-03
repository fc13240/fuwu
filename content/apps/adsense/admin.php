<?php
  
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * ECJIA 广告管理程序
 * @author songqian
 */
class admin extends ecjia_admin {
	public function __construct() {
		parent::__construct();
		/* 加载全局 js/css */
		RC_Script::enqueue_script('jquery-validate');
		RC_Script::enqueue_script('jquery-form');
		RC_Script::enqueue_script('smoke');
		RC_Style::enqueue_style('chosen');
		RC_Style::enqueue_style('uniform-aristo');
		RC_Script::enqueue_script('jquery-uniform');
		RC_Script::enqueue_script('jquery-chosen');
		
		RC_Script::enqueue_script('bootstrap-editable.min', RC_Uri::admin_url('statics/lib/x-editable/bootstrap-editable/js/bootstrap-editable.min.js'), array(), false, false);
		RC_Style::enqueue_style('bootstrap-editable', RC_Uri::admin_url('statics/lib/x-editable/bootstrap-editable/css/bootstrap-editable.css'), array(), false, false);
		
		//时间控件
		RC_Script::enqueue_script('bootstrap-datepicker', RC_Uri::admin_url('statics/lib/datepicker/bootstrap-datepicker.min.js'));
		RC_Style::enqueue_style('datepicker', RC_Uri::admin_url('statics/lib/datepicker/datepicker.css'));
		
		RC_Script::enqueue_script('bootstrap-placeholder', RC_Uri::admin_url('statics/lib/dropper-upload/bootstrap-placeholder.js'), array(), false, true);
		
		RC_Script::enqueue_script('adsense', RC_App::apps_url('statics/js/adsense.js', __FILE__));
		RC_Script::enqueue_script('ad_position', RC_App::apps_url('statics/js/ad_position.js', __FILE__));
		$js_lang = array(
			'ad_name_required' => RC_Lang::get('adsense::adsense.ad_name_required'),
			'gen_code_message' => RC_Lang::get('adsense::adsense.gen_code_message') 
		);
		RC_Script::localize_script('adsense', 'js_lang', $js_lang);
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('adsense::adsense.ads_list'), RC_Uri::url('adsense/admin/init')));
	}
	
	/**
	 * 广告列表页面
	 */
	public function init() {
		$this->admin_priv('adsense_manage');
		
		ecjia_screen::get_current_screen()->remove_last_nav_here();
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('adsense::adsense.ads_list')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id' => 'overview',
			'title' => RC_Lang::get('adsense::adsense.overview'),
			'content' => '<p>' . RC_Lang::get('adsense::adsense.adsense_list_help') . '</p>' 
		));
		ecjia_screen::get_current_screen()->set_help_sidebar('<p><strong>' . RC_Lang::get('adsense::adsense.more_info') . '</strong></p>' . '<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:广告列表" target="_blank">' . RC_Lang::get('adsense::adsense.about_adsense_list') . '</a>') . '</p>');
		$this->assign('ur_here', RC_Lang::get('adsense::adsense.ads_list'));
		$this->assign('action_link', array(
			'text' => RC_Lang::get('adsense::adsense.ads_add'),
			'href' => RC_Uri::url('adsense/admin/add') 
		));
		if (isset($_GET['pid'])) {
			$page = !empty($_GET['from_page']) ? intval($_GET['from_page']) : 1;
			$this->assign('back_position_list', array('text' => RC_Lang::get('adsense::adsense.position_list'), 'href' => RC_Uri::url('adsense/admin_position/init', array('page' => $page))));
		}
		$ads_list = $this->get_ad_list();
		$this->assign('ads_list', $ads_list);
		$this->assign('search_action', RC_Uri::url('adsense/admin/init'));
		$this->display('adsense_list.dwt');
	}
	
	/**
	 * 添加新广告页面
	 */
	public function add() {
		$this->admin_priv('adsense_update');
		
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('adsense::adsense.ads_add')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id' => 'overview',
			'title' => RC_Lang::get('adsense::adsense.overview'),
			'content' => '<p>' . RC_Lang::get('adsense::adsense.adsense_add_help') . '</p>' 
		));
		ecjia_screen::get_current_screen()->set_help_sidebar('<p><strong>' . RC_Lang::get('adsense::adsense.more_info') . '</strong></p>' . '<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:广告列表#.E6.B7.BB.E5.8A.A0.E5.B9.BF.E5.91.8A" target="_blank">' . RC_Lang::get('adsense::adsense.about_add_adsense') . '</a>') . '</p>');
		
		$this->assign('ur_here', RC_Lang::get('adsense::adsense.ads_add'));
		$this->assign('action_link', array('href' => RC_Uri::url('adsense/admin/init'), 'text' => RC_Lang::get('adsense::adsense.ads_list')));
		$position_list = $this->get_position_select_list();
		$this->assign('position_list', $position_list);
		$this->assign('action', 'insert');
		
		$ads['start_time'] = date('Y-m-d');
		$ads['end_time'] = date('Y-m-d', time() + 30 * 86400);
		$ads['enabled'] = 1;
		$this->assign('ads', $ads);
		$this->assign('form_action', RC_Uri::url('adsense/admin/insert'));
		$this->display('adsense_info.dwt');
	}
	
	/**
	 * 新广告的处理
	 */
	public function insert() {
		$this->admin_priv('adsense_update', ecjia::MSGTYPE_JSON);
		
		$id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
		$type = !empty($_POST['type']) ? intval($_POST['type']) : 0;
		$ad_name = !empty($_POST['ad_name']) ? trim($_POST['ad_name']) : '';
		$media_type = !empty($_POST['media_type']) ? intval($_POST['media_type']) : 0;
		if ($media_type === 0) {
			$ad_link = !empty($_POST['ad_link']) ? trim($_POST['ad_link']) : '';
		} else {
			$ad_link = !empty($_POST['ad_link2']) ? trim($_POST['ad_link2']) : '';
		}
		/* 获得广告的开始时期与结束日期 */
		$start_time = !empty($_POST['start_time']) ? RC_Time::local_strtotime($_POST['start_time']) : '';
		$end_time = !empty($_POST['end_time']) ? RC_Time::local_strtotime($_POST['end_time']) : '';
		/* 查看广告名称是否有重复 */
		$query = RC_DB::table('ad')->where('ad_name', $ad_name)->count();
		if (isset($_POST['ad_name'])) {
			if ($query > 0) {
				return $this->showmessage(RC_Lang::get('adsense::adsense.ad_name_exist'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		} else {
			return $this->showmessage(RC_Lang::get('adsense::adsense.ad_name_empty'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		/* 添加图片类型的广告 */
		if ($media_type === 0) {
			// 如果是本地上传
			if ($_POST['brand_logo_type'] == 1) {
				if (isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] == 0 || ! isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] != 'none') {
					$upload = RC_Upload::uploader('image', array('save_path' => 'data/afficheimg', 'auto_sub_dirs' => false));
					$image_info = $upload->upload($_FILES['ad_img']);
					if (!empty($image_info)) {
						$ad_code = $upload->get_position($image_info);
					} else {
						return $this->showmessage($upload->error(), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
					}
				}
				// 如果是远程连接
			} elseif ($_POST['brand_logo_type'] == 0) {
				/* 使用远程的LOGO图片 */
				if (!empty($_POST['url_logo'])) {
					if (strpos($_POST['url_logo'], 'http://') === false && strpos($_POST['url_logo'], 'https://') === false) {
						$ad_code = 'http://' . trim($_POST['url_logo']);
					} else {
						$ad_code = trim($_POST['url_logo']);
					}
				}
			}
		} elseif ($media_type === 1) {
			if (isset($_FILES['upfile_flash']['error']) && $_FILES['upfile_flash']['error'] == 0 || ! isset($_FILES['upfile_flash']['error']) && isset($_FILES['upfile_flash']['tmp_name']) && $_FILES['upfile_flash']['tmp_name'] != 'none') {
				$upload = RC_Upload::uploader('file', array('save_path' => 'data/afficheimg', 'auto_sub_dirs' => false));
				$upload->allowed_type(array('swf', 'fla'));
				$upload->allowed_mime(array('application/x-shockwave-flash', 'application/octet-stream'));
				$image_info = $upload->upload($_FILES['upfile_flash']);
				if (!empty($image_info)) {
					$ad_code = $upload->get_position($image_info);
				} else {
					return $this->showmessage($upload->error(), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
				}
			}
		} elseif ($media_type === 2) {
			if (!empty($_POST['ad_code'])) {
				$ad_code = $_POST['ad_code'];
			} else {
				return $this->showmessage(RC_Lang::get('adsense::adsense.ad_code_empty'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		} elseif ($media_type === 3) {
			if (!empty($_POST['ad_text'])) {
				$ad_code = $_POST['ad_text'];
			} else {
				return $this->showmessage(RC_Lang::get('adsense::adsense.ad_text_empty'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		}
		$ad_code = isset($ad_code) ? $ad_code : '';
		$data = array(
			'position_id' => $_POST['position_id'],
			'media_type' => $media_type,
			'ad_name' => $ad_name,
			'ad_link' => $ad_link,
			'ad_code' => $ad_code,
			'start_time' => $start_time,
			'end_time' => $end_time,
			'link_man' => !empty($_POST['link_man']) ? $_POST['link_man'] : '',
			'link_email' => !empty($_POST['link_email']) ? $_POST['link_email'] : '',
			'link_phone' => !empty($_POST['link_phone']) ? $_POST['link_phone'] : '',
			'click_count' => 0,
			'enabled' => !empty($_POST['enabled']) ? $_POST['enabled'] : '' 
		);
		$ad_id = RC_DB::table('ad')->insertGetId($data);
		/* 释放广告位缓存 */
		$ad_postion_db = RC_Model::model('adsense/orm_ad_position_model');
		$cache_key = sprintf('%X', crc32('adsense_position-' . $_POST['position_id']));
		$ad_postion_db->delete_cache_item($cache_key);
		ecjia_admin::admin_log($_POST['ad_name'], 'add', 'ads');
		
		$links[] = array('text' => RC_Lang::get('adsense::adsense.back_ads_list'), 'href' => RC_Uri::url('adsense/admin/init'));
		$links[] = array('text' => RC_Lang::get('adsense::adsense.continue_add_ad'), 'href' => RC_Uri::url('adsense/admin/add'));
		return $this->showmessage(RC_Lang::get('adsense::adsense.add_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('links' => $links, 'pjaxurl' => RC_Uri::url('adsense/admin/edit', array('id' => $ad_id))));
	}
	
	/**
	 * 广告编辑页面
	 */
	public function edit() {
		$this->admin_priv('adsense_update');
		
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('adsense::adsense.ads_edit')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id' => 'overview',
			'title' => RC_Lang::get('adsense::adsense.overview'),
			'content' => '<p>' . RC_Lang::get('adsense::adsense.adsense_edit_help') . '</p>' 
		));
		ecjia_screen::get_current_screen()->set_help_sidebar('<p><strong>' . RC_Lang::get('adsense::adsense.more_info') . '</strong></p>' . '<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:广告列表#.E7.BC.96.E8.BE.91.E5.B9.BF.E5.91.8A" target="_blank">' . RC_Lang::get('adsense::adsense.about_edit_adsense') . '</a>') . '</p>');
		$this->assign('ur_here', RC_Lang::get('adsense::adsense.ads_edit'));
		$this->assign('action_link', array('href' => RC_Uri::url('adsense/admin/init'), 'text' => RC_Lang::get('adsense::adsense.ads_list')));
		
		$ads_arr = RC_DB::table('ad')->where('ad_id', $_GET['id'])->first();
		$ads_arr['ad_name'] = htmlspecialchars($ads_arr['ad_name']);
		$ads_arr['start_time'] = RC_Time::local_date('Y-m-d', $ads_arr['start_time']);
		$ads_arr['end_time'] = RC_Time::local_date('Y-m-d', $ads_arr['end_time']);
		
		/* 标记为图片链接还是文字链接 */
		if (!empty($ads_arr['ad_code'])) {
			if (strpos($ads_arr['ad_code'], 'http://') === false) {
				$ads_arr['type'] = 1;
				$ads_arr['url'] = RC_Upload::upload_url($ads_arr['ad_code']);
			} else {
				$ads_arr['type'] = 0;
				$ads_arr['url'] = $ads_arr['ad_code'];
			}
		} else {
			$ads_arr['type'] = 0;
		}
		if ($ads_arr['media_type'] === 0) {
			if (strpos($ads_arr['ad_code'], 'http://') === false) {
				$src = $ads_arr['ad_code'];
				$this->assign('img_src', $src);
			} else {
				$src = $ads_arr['ad_code'];
				$this->assign('url_src', $src);
			}
		} elseif ($ads_arr['media_type'] === 1) {
			if (strpos($ads_arr['ad_code'], 'http://') === false) {
				$src = $ads_arr['ad_code'];
				$this->assign('flash_url', $src);
			} else {
				$src = $ads_arr['ad_code'];
				$this->assign('flash_url', $src);
			}
			$this->assign('src', $src);
		}
		if ($ads_arr['media_type'] === 0) {
			$this->assign('media_type', RC_Lang::get('adsense::adsense.ad_img'));
		} elseif ($ads_arr['media_type'] === 1) {
			$this->assign('media_type', RC_Lang::get('adsense::adsense.ad_flash'));
		} elseif ($ads_arr['media_type'] === 2) {
			$this->assign('media_type', RC_Lang::get('adsense::adsense.ad_html'));
		} elseif ($ads_arr['media_type'] === 3) {
			$this->assign('media_type', RC_Lang::get('adsense::adsense.ad_text'));
		}
		$this->assign('ads', $ads_arr);
		$position_list = $this->get_position_select_list();
		$this->assign('position_list', $position_list);
		$this->assign('form_action', RC_Uri::url('adsense/admin/update'));
		$this->display('adsense_info.dwt');
	}
	
	/**
	 * 广告编辑的处理
	 */
	public function update() {
		$this->admin_priv('adsense_update', ecjia::MSGTYPE_JSON);
		
		$type 		= !empty($_POST['media_type']) 	? intval($_POST['media_type']) 	: 0;
		$id 		= !empty($_POST['id']) 			? intval($_POST['id']) 			: 0;
		$ad_name	= !empty($_POST['ad_name']) 	? trim($_POST['ad_name']) 		: '';
		
		if ($type === 0) {
			$ad_link = !empty($_POST['ad_link']) ? trim($_POST['ad_link']) : '';
		} else {
			$ad_link = !empty($_POST['ad_link2']) ? trim($_POST['ad_link2']) : '';
		}
		$start_time = !empty($_POST['start_time']) ? RC_Time::local_strtotime($_POST['start_time']) : '';
		$end_time = !empty($_POST['end_time']) ? RC_Time::local_strtotime($_POST['end_time']) : '';
		$query = RC_DB::table('ad')->where('ad_name', $ad_name)->where('ad_id', '!=', $id)->count();
		if (!empty($ad_name)) {
			if ($query > 0) {
				return $this->showmessage(RC_Lang::get('adsense::adsense.ad_name_exist'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		} else {
			return $this->showmessage(RC_Lang::get('adsense::adsense.ad_name_empty'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
		/* 获取旧的LOGO地址,并删除 */
		$ad_info = RC_DB::table('ad')->where('ad_id', $id)->first();
		$ad_logo = $ad_info['ad_code'];
		/* 编辑图片类型的广告 */
		if ($type == 0) {
			$upload = RC_Upload::uploader('image', array('save_path' => 'data/afficheimg', 'auto_sub_dirs' => false));
			// 如果是远程链接
			if ($_POST['brand_logo_type'] == 0) {
				if (strpos($ad_logo, 'http://') === false && strpos($ad_logo, 'https://') === false) {
					$upload->remove($ad_logo);
				}
				if (strpos($_POST['url_logo'], 'http://') === false && strpos($_POST['url_logo'], 'https://') === false) {
					$ad_code = 'http://' . trim($_POST['url_logo']);
				} else {
					$ad_code = trim($_POST['url_logo']);
				}
				// 如果是本地上传
			} elseif ($_POST['brand_logo_type'] == 1) {
				if (isset($_FILES['ad_img']['error']) && $_FILES['ad_img']['error'] == 0 || ! isset($_FILES['ad_img']['error']) && isset($_FILES['ad_img']['tmp_name']) && $_FILES['ad_img']['tmp_name'] != 'none') {
					$upload = RC_Upload::uploader('image', array('save_path' => 'data/afficheimg', 'auto_sub_dirs' => false));
					$image_info = $upload->upload($_FILES['ad_img']);
					/* 如果要修改链接图片, 删除原来的图片 */
					if (!empty($image_info)) {
						if (strpos($ad_logo, 'http://') === false && strpos($ad_logo, 'https://') === false) {
							$upload->remove($ad_logo);
						}
						/* 获取新上传的LOGO的链接地址 */
						$ad_code = $upload->get_position($image_info);
					} else {
						return $this->showmessage($upload->error(), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
					}
				} else {
					$ad_code = $ad_logo;
				}
			}
		} elseif ($type == 1) {
			if (isset($_FILES['upfile_flash']['error']) && $_FILES['upfile_flash']['error'] == 0 || ! isset($_FILES['upfile_flash']['error']) && isset($_FILES['upfile_flash']['tmp_name']) && $_FILES['upfile_flash']['tmp_name'] != 'none') {
				$upload = RC_Upload::uploader('file', array('save_path' => 'data/afficheimg', 'auto_sub_dirs' => false));
				$upload->allowed_type(array('swf', 'fla'));
				$upload->allowed_mime(array('application/x-shockwave-flash', 'application/octet-stream'));
				$image_info = $upload->upload($_FILES['upfile_flash']);
				if (!empty($image_info)) {
					if (strpos($ad_logo, 'http://') === false && strpos($ad_logo, 'https://') === false) {
						$upload->remove($ad_logo);
					}
					$ad_code = $upload->get_position($image_info);
				} else {
					return $this->showmessage($upload->error(), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
				}
			} else {
				$ad_code = $ad_logo;
			}
		} elseif ($type == 2) {
			if (!empty($_POST['ad_code'])) {
				$ad_code = $_POST['ad_code'];
			} else {
				return $this->showmessage(RC_Lang::get('adsense::adsense.ad_code_empty'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		} elseif ($type == 3) {
			if (!empty($_POST['ad_text'])) {
				$ad_code = $_POST['ad_text'];
			} else {
				return $this->showmessage(RC_Lang::get('adsense::adsense.ad_text_empty'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			}
		}
		$ad_code = isset($ad_code) ? $ad_code : '';
		$data = array(
			'position_id' 	=> $_POST['position_id'],
			'ad_name' 		=> $ad_name,
			'ad_link' 		=> $ad_link,
			'ad_code' 		=> $ad_code,
			'start_time' 	=> $start_time,
			'end_time' 		=> $end_time,
			'link_man' 		=> !empty($_POST['link_man']) ? $_POST['link_man'] : '',
			'link_email' 	=> !empty($_POST['link_email']) ? $_POST['link_email'] : '',
			'link_phone' 	=> !empty($_POST['link_phone']) ? $_POST['link_phone'] : '',
			'enabled' 		=> !empty($_POST['enabled']) ? $_POST['enabled'] : '' 
		);
		/* 释放广告位缓存 */
		$ad_postion_db = RC_Model::model('adsense/orm_ad_position_model');
		$new_cache_key = sprintf('%X', crc32('adsense_position-' . $_POST['position_id']));
		$ad_postion_db->delete_cache_item($new_cache_key);
		$old_cache_key = sprintf('%X', crc32('adsense_position-' . $ad_info['position_id']));
		$ad_postion_db->delete_cache_item($old_cache_key);
		/* 更新数据 */
		RC_DB::table('ad')->where('ad_id', $id)->update($data);
		ecjia_admin::admin_log($ad_name, 'edit', 'ads');
		return $this->showmessage(RC_Lang::get('adsense::adsense.edit_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('adsense/admin/edit', array('id' => $id))));
	}
	
	/**
	 * 生成广告的JS代码
	 */
	public function add_js() {
		$this->admin_priv('adsense_manage');
		
		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('adsense::adsense.add_js_code')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id' => 'overview',
			'title' => RC_Lang::get('adsense::adsense.overview'),
			'content' => '<p>' . RC_Lang::get('adsense::adsense.adsense_genjs_help') . '</p>' 
		));
		ecjia_screen::get_current_screen()->set_help_sidebar('<p><strong>' . RC_Lang::get('adsense::adsense.more_info') . '</strong></p>' . '<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:广告列表#.E7.94.9F.E6.88.90.E5.B9.B6.E5.A4.8D.E5.88.B6JS.E4.BB.A3.E7.A0.81" target="_blank">' . RC_Lang::get('adsense::adsense.abount_genjs') . '</a>') . '</p>');
		$this->assign('ur_here', RC_Lang::get('adsense::adsense.add_js_code'));
		$this->assign('action_link', array('href' => RC_Uri::url('adsense/admin/init'), 'text' => RC_Lang::get('adsense::adsense.ads_list')));
		
		$lang_list = array(
			'UTF8' => RC_Lang::get('system::system.charset.utf8'),
			'GB2312' => RC_Lang::get('system::system.charset.zh_cn'),
			'BIG5' => RC_Lang::get('system::system.charset.zh_tw') 
		);
		$this->assign('lang_list', $lang_list);
		
		$js_code = '<script type="text/javascript"';
		$js_code .= ' src="' . SITE_URL . '/affiche.php?act=js&type=' . $_REQUEST['type'] . '&ad_id=' . intval($_REQUEST['id']) . '"></script>';
		$this->assign('js_code', $js_code);
		
		$site_url = SITE_URL . '/affiche.php?act=js&type=' . $_REQUEST['type'] . '&ad_id=' . intval($_REQUEST['id']);
		$this->assign('url', $site_url);
		$this->display('adsense_js.dwt');
	}
	
	/**
	 * 编辑广告名称
	 */
	public function edit_ad_name() {
		$this->admin_priv('adsense_update', ecjia::MSGTYPE_JSON);
		
		$id = intval($_POST['pk']);
		$ad_name = trim($_POST['value']);
		if (!empty($ad_name)) {
			if (RC_DB::table('ad')->where('ad_name', $ad_name)->count() != 0) {
				return $this->showmessage(sprintf(RC_Lang::get('adsense::adsense.ad_name_exist'), $ad_name), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
			} else {
				$data = array(
					'ad_name' => $ad_name 
				);
				RC_DB::table('ad')->where('ad_id', $id)->update($data);
				ecjia_admin::admin_log($ad_name, 'edit', 'ads');
				return $this->showmessage(RC_Lang::get('adsense::adsense.edit_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('content' => stripslashes($ad_name)));
			}
		} else {
			return $this->showmessage(RC_Lang::get('adsense::adsense.ad_name_empty'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		}
	}
	
	/**
	 * 删除广告
	 */
	public function remove() {
		$this->admin_priv('adsense_delete', ecjia::MSGTYPE_JSON);
		
		$id = intval($_GET['id']);
		$info = RC_DB::table('ad')->where('ad_id', $id)->first();
		if (strpos($info['ad_code'], 'http://') === false && strpos($info['ad_code'], 'https://') === false) {
			$disk = RC_Filesystem::disk();
			$disk->delete(RC_Upload::upload_path() . $info['ad_code']);
		}
		RC_DB::table('ad')->where('ad_id', $id)->delete();
		$ad_postion_db = RC_Model::model('adsense/orm_ad_position_model');
		$cache_key = sprintf('%X', crc32('adsense_position-' . $info['position_id']));
		$ad_postion_db->delete_cache_item($cache_key);
		ecjia_admin::admin_log($info['ad_name'], 'remove', 'ads');
		return $this->showmessage(RC_Lang::get('adsense::adsense.drop_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS);
	}
	
	/**
	 * 删除附件
	 */
	public function delfile() {
		$this->admin_priv('adsense_delete', ecjia::MSGTYPE_JSON);
		
		$id = intval($_GET['id']);
		$old_url = RC_DB::table('ad')->where('ad_id', $id)->pluck('ad_code');
		$disk = RC_Filesystem::disk();
		$disk->delete(RC_Upload::upload_path() . $old_url);
		$data = array(
			'ad_code' => '' 
		);
		RC_DB::table('ad')->where('ad_id', $id)->update($data);
		return $this->showmessage(RC_Lang::get('adsense::adsense.drop_success'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('adsense/admin/edit', array('id' => $id))));
	}
	
	/**
	 * 获取广告列表
	 */
	private function get_ad_list() {
		$filter = $where = array();
		$filter['sort_by'] = empty($_GET['sort_by']) ? 'ad.ad_id' : trim($_GET['sort_by']);
		$filter['sort_order'] = empty($_GET['sort_order']) ? 'DESC' : trim($_GET['sort_order']);
		$filter['keywords'] = empty($_GET['keywords']) ? '' : trim($_GET['keywords']);
		$pid = empty($_GET['pid']) ? 0 : intval($_GET['pid']);
		
		$ad_db = RC_DB::table('ad');
		$ad_db->leftJoin('ad_position', 'ad_position.position_id', '=', 'ad.position_id');
		if ($filter['keywords']) {
			$ad_db->where('ad_name', 'like', '%' . mysql_like_quote($filter['keywords']) . '%');
		}
		if (isset($_GET['media_type'])) {
			$filter['media_type'] = intval($_GET['media_type']);
			$ad_db->where('media_type', '=', intval($_GET['media_type']));
		} else {
			$filter['media_type'] = '';
		}
		if ($pid > 0) {
			$ad_db->where('ad.position_id', '=', $pid);
		}
		
		$count = $ad_db->count();
		$page = new ecjia_page($count, 10, 5);
		$ad_db->select('ad.*', 'ad_position.position_name')->groupby('ad.ad_id')->orderby($filter['sort_by'], $filter['sort_order'])->take(10)->skip($page->start_id - 1);
		$data = $ad_db->get();
		
		$arr = array();
		if (isset($data)) {
			foreach ($data as $rows) {
				/* 广告类型的名称 */
				$rows['type'] = $rows['media_type'] == 0 ? RC_Lang::get('adsense::adsense.ad_img') : '';
				$rows['type'] .= $rows['media_type'] == 1 ? RC_Lang::get('adsense::adsense.ad_flash') : '';
				$rows['type'] .= $rows['media_type'] == 2 ? RC_Lang::get('adsense::adsense.ad_html') : '';
				$rows['type'] .= $rows['media_type'] == 3 ? RC_Lang::get('adsense::adsense.ad_text') : '';
				$rows['start_date'] = RC_Time::local_date(ecjia::config('date_format'), $rows['start_time']);
				$rows['end_date'] = RC_Time::local_date(ecjia::config('date_format'), $rows['end_time']);
				if ($rows['media_type'] == 0 && file_exists(RC_Upload::upload_path($rows['ad_code']))) {
					$rows['image'] = !empty($rows['ad_code']) ? RC_Upload::upload_url($rows['ad_code']) : '';
				}
				$arr[] = $rows;
			}
		}
		return array('item' => $arr, 'filter' => $filter, 'page' => $page->show(2), 'desc' => $page->page_desc());
	}
	
	/**
	 * 获取广告位置下拉列表
	 */
	private function get_position_select_list() {
		$data = RC_DB::table('ad_position')->select('position_id', 'position_name', 'ad_width', 'ad_height')->orderBy('position_id', 'desc')->get();
		$position_list = array();
		if (!empty($data)) {
			foreach ($data as $row) {
				$position_list[$row['position_id']] = addslashes($row['position_name']) . ' [' . $row['ad_width'] . 'x' . $row['ad_height'] . ']';
			}
		}
		return $position_list;
	}
}

// end