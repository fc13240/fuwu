<?php
  
defined('IN_ECJIA') or exit('No permission resources.');

//定义在后台
define('IN_MERCHANT', true);

abstract class ecjia_merchant extends ecjia_base implements ecjia_template_fileloader {

	private $public_route;

	public function __construct() {
		parent::__construct();

		self::$controller = static::$controller;
		self::$view_object = static::$view_object;

		if (defined('DEBUG_MODE') == false) {
		    define('DEBUG_MODE', 2);
		}

		/* 新加载全局方法 */
		RC_Loader::load_sys_func('global');

		RC_Loader::load_sys_func('general_template');

		// Clears file status cache
		clearstatcache();

		// load Lang file
		RC_Lang::load(array('system/system', 'system/log_action'));
		if (ROUTE_M == RC_Config::get('system.admin_entrance')) {
			RC_Lang::load('system/' . ROUTE_C);
		}

		// Catch plugins that include admin-header.php before admin.php completes.
		if ( empty( ecjia_merchant_screen::$current_screen ) ) {
		    ecjia_merchant_screen::set_current_screen();
		}

		RC_Hook::add_action('merchant_print_main_header', array(ecjia_merchant_screen::$current_screen, 'render_screen_meta'));

		$this->public_route = array(
		    'staff/privilege/login',
		    'staff/privilege/signin',
		    'staff/get_password/forget_pwd',
		    'staff/get_password/reset_pwd_mail',
		    'staff/get_password/reset_pwd_form',
		    'staff/get_password/reset_pwd',
			'staff/get_password/forget_fast',
			'staff/get_password/fast_reset_pwd',
			'staff/get_password/get_code',
			'staff/get_password/get_code_value',
			'staff/get_password/get_code_form',
			'staff/get_password/mobile_reset',
			'staff/get_password/mobile_reset_pwd',
		    'franchisee/merchant/init',
		    'franchisee/merchant/init',
		    'franchisee/merchant/get_code_value',
		    'franchisee/merchant/insert',
		    'franchisee/merchant/view',
		    'franchisee/merchant/view_post',
		    'franchisee/merchant/remove_apply',
		    'franchisee/merchant/drop_file',
		    'franchisee/merchant/get_region',
			'franchisee/merchant/getgeohash',
			'merchant/merchant/shopinfo',
		);
		$this->public_route = RC_Hook::apply_filters('merchant_access_public_route', $this->public_route);

		// 判断用户是否登录
		if (!$this->_check_login()) {
		    RC_Session::destroy();
		    if (is_pjax()) {
		        ecjia_screen::$current_screen->add_nav_here(new admin_nav_here(__('系统提示')));
		        return $this->showmessage(RC_Lang::get('system::system.priv_error'), ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR, array('links' => array(array('text' => __('重新登录'), 'href' => RC_Uri::url('staff/privilege/login')))));
		    } elseif (is_ajax()) {
		        return $this->showmessage(RC_Lang::get('system::system.priv_error'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		    } else {
		        RC_Cookie::set('admin_login_referer', RC_Uri::current_url());
		        return $this->redirect(RC_Uri::url('staff/privilege/login'));
		    }
		}

		if (RC_Config::get('system.debug')) {
			error_reporting(E_ALL);
		} else {
			error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
		}

		$rc_script = RC_Script::instance();
		$rc_style = RC_Style::instance();
		ecjia_merchant_loader::default_scripts($rc_script);
		ecjia_merchant_loader::default_styles($rc_style);

		$this->load_cachekey();

		$this->load_default_script_style();
		$staff_avatar = RC_DB::TABLE('staff_user')->where('user_id', RC_Session::get('staff_id'))->pluck('avatar');
		
		$this->assign('ecjia_staff_logo', $staff_avatar);
		$this->assign('ecjia_merchant_cptitle', RC_Session::get('store_name'));
		$this->assign('ecjia_merchant_cpname', RC_Session::get('store_name'));
		$this->assign('ecjia_main_static_url', $this->get_main_static_url());
		$this->assign('ecjia_system_static_url', RC_Uri::system_static_url() . '/');
		
		//头部左侧通知
		$count = RC_DB::table('notifications')->where('notifiable_id', $_SESSION['staff_id'])->whereNull('read_at')->count();
		$list = RC_DB::table('notifications')->where('notifiable_id', $_SESSION['staff_id'])->whereNull('read_at')->get();
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				if (!empty($v['data'])) {
					$content = json_decode($v['data'], true);
					$list[$k]['content'] = $content['body'];
				}
			}
		}
		$this->assign('ecjia_merchant_notice_count', $count);
		$this->assign('ecjia_merchant_notice_list', $list);
		
		//底部右侧网店信息
		$shopinfo_list = RC_DB::table('article')
			->select('article_id', 'title')
	    	->where('cat_id', 0)
	    	->orderby('article_id', 'asc')
	    	->get();
		$this->assign('ecjia_merchant_shopinfo_list', $shopinfo_list);
		
		//店铺导航背景图
		$background_url = RC_DB::table('merchants_config')->where('store_id', $_SESSION['store_id'])->where('code', 'shop_nav_background')->pluck('value');
		if (!empty($background_url) && file_exists(RC_Upload::upload_path($background_url))) {
			$background_url = RC_Upload::upload_url($background_url);
		}
		$this->assign('background_url', $background_url);
		
		//左侧qq链接中的site
		$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$this->assign('http_host', $http_host);
		
		RC_Hook::do_action('ecjia_merchant_finish_launching');
	}

	protected function session_start()
	{
	    RC_Hook::add_filter('royalcms_session_name', function ($sessin_name) {
	        return RC_Config::get('session.session_merchant_name');
	    });

        RC_Hook::add_filter('royalcms_session_id', function ($sessin_id) {
            return RC_Hook::apply_filters('ecjia_merchant_session_id', '');
        });

        RC_Session::start();
	}

	public function create_view() {
	    $view = new ecjia_view($this);

	    $view->setTemplateDir($this->get_main_template_dir());
	    if (!in_array($this->get_template_dir(), $view->getTemplateDir())) {
	        $view->addTemplateDir($this->get_template_dir());
	    }
	    $view->setCompileDir(TEMPLATE_COMPILE_PATH . 'merchant' . DIRECTORY_SEPARATOR);

	    if (RC_Config::get('system.debug')) {
	        $view->caching = Smarty::CACHING_OFF;
	        $view->debugging = true;
	        $view->force_compile = true;
	    } else {
	        $view->caching = Smarty::CACHING_OFF;
	        $view->debugging = false;
	        $view->force_compile = false;
	    }

	    return $view;
	}
	
	public function get_main_template_dir() {
	    if (RC_Loader::exists_site_app('merchant')) {
	        $dir = SITE_APP_PATH . 'merchant' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'merchant' . DIRECTORY_SEPARATOR;
	    } else {
	        $dir = RC_APP_PATH . 'merchant' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'merchant' . DIRECTORY_SEPARATOR;
	    }
	    
	    return $dir;
	}
	
	public function get_main_static_dir() {
	    if (RC_Loader::exists_site_app('merchant')) {
	        $dir = SITE_APP_PATH . 'merchant' . DIRECTORY_SEPARATOR . 'statics' . DIRECTORY_SEPARATOR;
	    } else {
	        $dir = RC_APP_PATH . 'merchant' . DIRECTORY_SEPARATOR . 'statics' . DIRECTORY_SEPARATOR;
	    }
	     
	    return $dir;
	}
	
	public function get_main_static_url() {
	    return dirname(RC_App::app_dir_url(__FILE__)) . '/statics/';
	}
	
	/**
	 * 获得商家后台模板目录
	 * @return string
	 */
	public function get_template_dir()
	{
        if (RC_Loader::exists_site_app(ROUTE_M)) {
            $dir = SITE_APP_PATH . ROUTE_M . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'merchant' . DIRECTORY_SEPARATOR;
        } else {
            $dir = RC_APP_PATH . ROUTE_M . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'merchant' . DIRECTORY_SEPARATOR;
        }

        return $dir;
	}
	
	/**
	 * 获得商家后台模版文件
	 */
	public function get_template_file($file)
	{
	    // 判断是否使用绝对路径的模板文件
	    if (strpos($file, '/') !== 0 && strpos($file, ":\\") !== 1) {
	        $file = $this->get_template_dir() . $file;
	    }
	
	    // 添加模板后缀
	    if (! preg_match('@\.[a-z]+$@', $file)) {
	        $file .= RC_Config::get('system.tpl_fix');
	    }
	
	    // 将目录全部转为小写
	    if (is_file($file)) {
	        return $file;
	    } else {
	        return str_replace($this->get_template_dir(), '', $file);
	    }
	}

	/**
	 * 加载缓存key
	 */
	protected function load_cachekey() {
	    $res = RC_Api::api('system', 'system_cache');
	    if (! empty($res)) {
	        foreach ($res as $cache_handle) {
	            ecjia_update_cache::make()->register($cache_handle->getCode(), $cache_handle);
	        }
	    }
	}

	/**
	 * 后台判断是否登录
	 */
	private function _check_login() {
		$route_controller = ROUTE_M . '/' . ROUTE_C . '/' . ROUTE_A;
		if (in_array($route_controller, $this->public_route)) {
		    return true;
		}

		/* 验证管理员身份 */
		if (isset($_SESSION['staff_id']) && intval($_SESSION['staff_id']) > 0) {
		    return true;
		}
		/* session 不存在，检查cookie */
		$staff_id = RC_Cookie::get('ECJAP[staff_id]');
		$staff_pass = RC_Cookie::get('ECJAP[staff_pass]');

		if (!empty($staff_id) && !empty($staff_pass)) {
			// 找到了cookie, 验证cookie信息
			$row = RC_DB::TABLE('staff_user')->where('user_id', intval($staff_id))->select('user_id', 'name', 'password', 'action_list', 'last_login')->get();
			if (!empty($row)) {
				// 检查密码是否正确
				if (md5($row['password'] . ecjia::config('hash_code')) == $staff_pass) {
					!isset($row['last_login']) && $row['last_login'] = '';
					$this->admin_session($row['user_id'], $row['name'], $row['action_list'], $row['last_login']);

					// 更新最后登录时间和IP
					$data = array(
						'last_login' => RC_Time::gmtime(),
						'last_ip'    => RC_Ip::client_ip()
					);
					RC_DB::table('staff_user')->where('user_id', $_SESSION['staff_id'])->update($data);
					return true;
				} else {
					RC_Cookie::delete('ECJAP[staff_id]');
					RC_Cookie::delete('ECJAP[staff_pass]');
					return false;
				}
			} else {
				// 没有找到这个记录
				RC_Cookie::delete('ECJAP[staff_id]');
				RC_Cookie::delete('ECJAP[staff_pass]');
				return false;
			}
		} else {
			unset($staff_id);
			unset($staff_pass);
			return false;
		}
	}

	/**
	 * 加载后台模板
	 * @param string $file 文件名 格式：file or file/file
	 */
	public final function display($tpl_file = null, $cache_id = null, $show = true, $options = array()) {
	    if (strpos($tpl_file, 'string:') !== 0) {
	        if (RC_File::file_suffix($tpl_file) !== 'php') {
	            $tpl_file = $tpl_file . '.php';
	        }
	    }
		return parent::display($tpl_file, $cache_id, $show, $options);
	}

	/**
	 * 捕获输出为变量
	 * @param string $file 文件名 格式：file or file/file
	 */
	public final function fetch($tpl_file = null, $cache_id = null, $options = array()) {
	    if (strpos($tpl_file, 'string:') !== 0) {
	        if (RC_File::file_suffix($tpl_file) !== 'php') {
	            $tpl_file = $tpl_file . '.php';
	        }
	    }
		return parent::fetch($tpl_file, $cache_id, $options);
	}

	/**
	 * 直接跳转
	 *
	 * @param string $url
	 * @param int $code
	 */
	public function redirect($url, $code = 302) {
	    parent::redirect($url, $code);
	}

	/**
	 * 信息提示
	 *
	 * @param string $msg
	 *            提示内容
	 * @param string $url
	 *            跳转URL
	 * @param int $time
	 *            跳转时间
	 * @param null $tpl
	 *            模板文件
	 */
	protected function message($msg = '操作成功', $url = null, $time = 2, $tpl = null)
	{
	    $url = $url ? "window.location.href='" . $url . "'" : "window.history.back(-1);";
	    $system_tpl = $this->get_main_template_dir() . RC_Config::get('system.tpl_message');

// 	    switch ($state) {
// 	    	case 1:
// 	    		$this->assign('page_state', array('icon' => 'glyphicon glyphicon-ok-sign', 'msg' => __('操作成功'), 'class' => 'ecjiafc-blue'));
// 	    		break;
// 	    	case 2:
// 	    		$this->assign('page_state', array('icon' => 'glyphicon glyphicon-info-sign', 'msg' => __('操作提示'), 'class' => 'ecjiafc-blue'));
// 	    		break;
// 	    	case 3:
// 	    		$this->assign('page_state', array('icon' => 'glyphicon glyphicon-exclamation-sign', 'msg' => __('操作警告'), 'class' => ''));
// 	    		break;
// 	    	default:
	    		$this->assign('page_state', array('icon' => 'glyphicon glyphicon-remove-circle', 'msg' => __('操作错误'), 'class' => 'ecjiafc-red'));
// 	    }
	    

	        
        if (file_exists($system_tpl)) {
	        $this->assign(array(
	            'msg' => $msg,
	            'url' => $url,
	            'time' => $time
	        ));
	        $this->display($system_tpl);
	    } else {
	        return parent::message($msg, $url, $time, $tpl);
	    }

	}

	/**
	 * 设置管理员的session内容
	 *
	 * @access  public
	 * @param   integer $store_id       店铺id
	 * @param   string  $merchants_name 店铺名称
	 * @param   integer $user_id        管理员编号
	 * @param   integer $mobile         管理员手机号
	 * @param   string  $username       管理员姓名
	 * @param   string  $action_list    权限列表
	 * @param   string  $last_time      最后登录时间
	 * @return  void
	 */
	public function admin_session($store_id, $merchants_name, $user_id, $mobile, $username, $action_list, $last_time) {
		$_SESSION['store_id']    		= $store_id;
		$_SESSION['store_name']    		= $merchants_name;
		$_SESSION['staff_id']    		= $user_id;
		$_SESSION['staff_mobile']  		= $mobile;
		$_SESSION['staff_name']  		= $username;
		$_SESSION['action_list'] 		= $action_list;
		$_SESSION['last_check_order']  	= $last_time; // 用于保存最后一次检查订单的时间
	}

	/**
	 * 判断管理员对某一个操作是否有权限。
	 *
	 * 根据当前对应的action_code，然后再和用户session里面的action_list做匹配，以此来决定是否可以继续执行。
	 * @param     string    $priv_str    操作对应的priv_str
	 * @param     string    $msg_type    返回的类型 html,json
	 * @return true/false
	 */
	public final function admin_priv($priv_str, $msg_type = ecjia::MSGTYPE_HTML , $msg_output = true) {
		if (ecjia_merchant_menu::singleton()->admin_priv($priv_str)) {
		    return true;
		} else {
		    if ($msg_output) {
		        if ($msg_type == ecjia::MSGTYPE_JSON && is_ajax() && !is_pjax()) {
		            return $this->showmessage(__('对不起，您没有执行此项操作的权限！'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		        } else {
		            ecjia_screen::$current_screen->add_nav_here(new admin_nav_here(__('系统提示')));
		            return $this->showmessage(__('对不起，您没有执行此项操作的权限！'), ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR);
		        }
		    } else {
		        return false;
		    }
		}
	}

	public final function load_default_script_style() {
		// 加载样式
	    // Bootstrap framework
	    RC_Style::enqueue_style('googleapis-fonts');
	    RC_Style::enqueue_style('bootstrap');
	    RC_Style::enqueue_style('bootstrap-reset');
	    RC_Style::enqueue_style('ecjia-merchant-ui');

	    RC_Style::enqueue_style('ecjia-mh-font-awesome');
	    RC_Style::enqueue_style('ecjia-mh-owl-carousel');
	    RC_Style::enqueue_style('ecjia-mh-owl-theme');
	    RC_Style::enqueue_style('ecjia-mh-owl-transitions');
	    RC_Style::enqueue_style('ecjia-mh-table-responsive');
	    RC_Style::enqueue_style('ecjia-mh-jquery-easy-pie-chart');

	    RC_Style::enqueue_style('ecjia-mh-function');
	    RC_Style::enqueue_style('ecjia-mh-page');
	    RC_Style::enqueue_style('ecjia-mh-chosen');

		// 加载脚本
		// jquery
		RC_Script::enqueue_script('jquery');
		RC_Script::enqueue_script('bootstrap');

		// jquery pjax
		RC_Script::enqueue_script('jquery-pjax');

		// merchant js lib
		RC_Script::enqueue_script('ecjia-mh-jquery-customSelect');
		RC_Script::enqueue_script('ecjia-mh-jquery-dcjqaccordion');
		RC_Script::enqueue_script('ecjia-mh-jquery-nicescroll');
		RC_Script::enqueue_script('ecjia-mh-jquery-scrollTo');
		RC_Script::enqueue_script('ecjia-mh-jquery-sparkline');
		RC_Script::enqueue_script('ecjia-mh-jquery-stepy');
		RC_Script::enqueue_script('ecjia-mh-jquery-tagsinput');
		RC_Script::enqueue_script('ecjia-mh-jquery-validate');
		RC_Script::enqueue_script('ecjia-mh-jquery-easy-pie-chart');
		RC_Script::enqueue_script('ecjia-mh-jquery-actual');
		RC_Script::enqueue_script('ecjia-mh-jquery-migrate');
		RC_Script::enqueue_script('ecjia-mh-jquery-quicksearch');

		RC_Script::enqueue_script('ecjia-mh-morris-script');
		RC_Script::enqueue_script('ecjia-mh-owl-carousel');
		RC_Script::enqueue_script('ecjia-mh-respond');
		RC_Script::enqueue_script('ecjia-mh-slider');
		RC_Script::enqueue_script('ecjia-mh-sparkline-chart');
		RC_Script::enqueue_script('ecjia-mh-themes');
		RC_Script::enqueue_script('ecjia-mh-xchart');
		RC_Script::enqueue_script('ecjia-mh-chosen-jquery');
		RC_Script::enqueue_script('ecjia-mh-chart');

		// js cookie plugin
		RC_Script::enqueue_script('jquery-cookie');
		RC_Script::enqueue_script('js-json');
		// scroll 处理触摸事件
		RC_Script::enqueue_script('nicescroll');
		// to top 右侧跳到顶部
		RC_Script::enqueue_script('jquery-ui-totop');

		RC_Script::enqueue_script('ecjia-merchant');
		RC_Script::enqueue_script('ecjia-merchant-ui');

		$admin_jslang = array(
			'display_sidebar'	=> __('显示侧边栏'),
			'hide_sidebar'		=> __('隐藏侧边栏'),
			'search_check'		=> __('请先输入搜索信息'),
			'search_no_message'	=> __('未搜索到导航信息'),
			'success'			=> __('操作成功'),
			'fail'				=> __('操作失败'),
			'confirm_jump'		=> __('是否确认跳转？'),
			'ok'				=> __('确定'),
			'cancel'			=> __('取消'),
			'request_failed'	=> __('请求失败，错误编号：'),
			'error_msg'			=> __('，错误信息：')
		);
		RC_Script::localize_script('ecjia-merchant', 'admin_lang', $admin_jslang );
	}


	protected function load_hooks() {
		RC_Hook::add_action('merchant_head', array('ecjia_merchant_loader', 'admin_enqueue_scripts'), 1 );
		RC_Hook::add_action('merchant_print_scripts', array('ecjia_merchant_loader', 'print_head_scripts'), 20 );
		RC_Hook::add_action('merchant_print_footer_scripts', array('ecjia_merchant_loader', '_admin_footer_scripts') );
		RC_Hook::add_action('merchant_print_styles', array('ecjia_merchant_loader', 'print_admin_styles'), 20 );
		RC_Hook::add_action('merchant_print_header_nav', array(__CLASS__, 'display_admin_header_nav'));
		RC_Hook::add_action('merchant_sidebar_collapse_search', array(__CLASS__, 'display_admin_sidebar_nav_search'), 9);
		RC_Hook::add_action('merchant_sidebar_collapse', array(__CLASS__, 'display_admin_sidebar_nav'), 9);
		RC_Hook::add_filter('upload_default_random_filename', array('ecjia_utility', 'random_filename'));
		RC_Hook::add_action('merchant_print_footer_scripts', array(ecjia_notification::make(), 'printScript') );

		RC_Package::package('app::merchant')->loadClass('hooks.merchant_merchant', false);

		$system_plugins = ecjia_config::instance()->get_addon_config('system_plugins', true);
		if (is_array($system_plugins)) {
		    foreach ($system_plugins as $plugin_file) {
		        RC_Plugin::load_files($plugin_file);
		    }
		}

		$merchant_plugins = ecjia_config::instance()->get_addon_config('merchant_plugins', true);
		if (is_array($merchant_plugins)) {
		    foreach ($merchant_plugins as $plugin_file) {
		        RC_Plugin::load_files($plugin_file);
		    }
		}

		$apps = ecjia_app::installed_app_floders();
		if (is_array($apps)) {
			foreach ($apps as $app) {
				RC_Package::package('app::'.$app)->loadClass('hooks.merchant_' . $app, false);
			}
		}
	}

	/**
	 * 生成admin_menu对象
	 */
	public static function make_admin_menu($action, $name, $link, $sort = 99, $target = '_self') {
	    return new admin_menu($action, $name, $link, $sort, $target);
	}

	/**
	 * 记录管理员的操作内容
	 *
	 * @access  public
	 * @param   string      $sn         数据的唯一值
	 * @param   string      $action     操作的类型
	 * @param   string      $content    操作的内容
	 * @return  void
	 */
	public final static function admin_log($sn, $action, $content) {
	    $log_info = ecjia_admin_log::instance()->get_message($sn, $action, $content);

	    $data = array(
	        'log_time' 		=> RC_Time::gmtime(),
	    	'store_id'		=> !empty($_SESSION['store_id']) ? $_SESSION['store_id'] : 0,
	        'user_id' 		=> !empty($_SESSION['staff_id']) ? $_SESSION['staff_id'] : 0,
	        'log_info' 		=> stripslashes($log_info),
	        'ip_address' 	=> RC_Ip::client_ip(),
	    );

	    RC_DB::table('staff_log')->insertGetId($data);
	}


	/**
	 * 获取当前管理员信息
     *
	 * @return  array
	 */
	public final static function admin_info() {

	    $admin_info = RC_DB::table('staff_user')->where('user_id', intval($_SESSION[staff_id]))->first();

	    if (!empty($admin_info)) {
	        return $admin_info;
	    }
	    return false;
	}


	/**
	 * 添加IE支持的header信息
	 */
	public static function _ie_support_header() {
		if (is_ie()) {
			echo "\n";
			echo '<!--[if lte IE 8]>'. "\n";
			echo '<link rel="stylesheet" href="' . RC_Uri::admin_url() . '/statics/lib/ie/ie.css" />'. "\n";
			echo '<![endif]-->'. "\n";
			echo "\n";
			echo '<!--[if lt IE 9]>'. "\n";
			echo '<script src="' . RC_Uri::admin_url() . '/statics/lib/ie/html5.js"></script>'. "\n";
			echo '<script src="' . RC_Uri::admin_url() . '/statics/lib/ie/respond.min.js"></script>'. "\n";
			echo '<script src="' . RC_Uri::admin_url() . '/statics/lib/flot/excanvas.min.js"></script>'. "\n";
			echo '<![endif]-->'. "\n";
		}
	}


    public static function display_admin_header_nav() {
        $menus = ecjia_merchant_menu::singleton()->admin_menu();
        $screen = ecjia_merchant_screen::get_current_screen();

        echo '<ul class="nav navbar-nav">';
        foreach ($menus as $key => $group) {
            if ($group) {
                foreach ($group as $k => $menu) {
                    if ($menu->has_submenus()) {
                        if ($menu->base && $screen->parent_base && $menu->base == $screen->parent_base) {
                            echo '<li class="dropdown active">'.PHP_EOL;
                        } else {
                            echo '<li class="dropdown">'.PHP_EOL;
                        }
                        if ($menu->link) {
                            echo '  <a class="dropdown-toggle" data-toggle="dropdown" href="' . $menu->link . '" target="' . $menu->target . '">'.PHP_EOL;
                        } else {
                            echo '  <a class="dropdown-toggle" data-toggle="dropdown" href="javascript:;" target="' . $menu->target . '">'.PHP_EOL;
                        }
                        echo '  <div class="text-center">'.PHP_EOL;
                        echo '      <i class="fa '.$menu->icon.' fa-3x"></i><br>'.$menu->name.' <span class="caret"></span>'.PHP_EOL;
                        echo '  </div>'.PHP_EOL;
                        echo '  </a>'.PHP_EOL;
                        echo '  <ul class="dropdown-menu" role="menu">'.PHP_EOL;

                        if ($menu->submenus) {
                            foreach ($menu->submenus as $child) {
                                if ($child->action == 'divider') {
                                    echo '      <li class="divider"></li>'.PHP_EOL;
                                } else {
                                    echo '      <li><a href="'.$child->link.'"><i class="fa '.$child->icon.'"></i> '.$child->name.'</a></li>'.PHP_EOL;
                                }
                            }
                        }
                        echo '  </ul>'.PHP_EOL;
                        echo '</li>'.PHP_EOL;
                    } else {
                        if ($menu->base && $screen->parent_base && $menu->base == $screen->parent_base) {
                            echo '<li class="active">'.PHP_EOL;
                        } else {
                            echo '<li>'.PHP_EOL;
                        }
                        if ($menu->link) {
                            echo '  <a href="' . $menu->link . '" target="' . $menu->target . '">'.PHP_EOL;
                        } else {
                            echo '  <a href="javascript:;" target="' . $menu->target . '">'.PHP_EOL;
                        }
                        echo '  <div class="text-center">'.PHP_EOL;
                        echo '      <i class="fa '.$menu->icon.' fa-3x"></i><br>'.$menu->name.PHP_EOL;
                        echo '  </div>'.PHP_EOL;
                        echo '  </a>'.PHP_EOL;
                        echo '</li>'.PHP_EOL;
                    }
                }
            }
        }
        echo '</ul>';
    }


    public static function display_admin_sidebar_nav_search() {
        $menus = ecjia_merchant_menu::singleton()->admin_menu();
        $screen = ecjia_merchant_screen::get_current_screen();

        foreach ($menus as $key => $group) {
            if ($group) {
                foreach ($group as $k => $menu) {
                    if ($menu->has_submenus()) {

                        if ($menu->submenus) {
                            foreach ($menu->submenus as $child) {
                                if ($child->action == 'divider') {
                                    echo '      <li class="divider"></li>'.PHP_EOL;
                                } else {
                                    echo '      <li><a href="'.$child->link.'"><i class="fa '.$child->icon.'"></i> '.$child->name.'</a></li>'.PHP_EOL;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public static function display_admin_sidebar_nav() {
        $menus = ecjia_admin_menu::singleton()->admin_menu();

        if (!empty($menus['apps'])) {
            foreach ($menus['apps'] as $k => $menu) {
                echo '<div class="accordion-group">';
                echo '<div class="accordion-heading">';
                echo '<a class="accordion-toggle" href="#collapse' . $k . '" data-parent="#side_accordion" data-toggle="collapse">';
                echo '<i class="icon-folder-close"></i> ' . $menu->name;
                echo '</a>';
                echo '</div>';
                if ($menu->has_submenus) {
                    echo '<div class="accordion-body collapse" id="collapse' . $k . '">';
                    echo '<div class="accordion-inner">';
                    echo '<ul class="nav nav-list">';
                    if ($menu->submenus) {
                        foreach ($menu->submenus as $child) {
                            if ($child->action == 'divider') {
                                echo '<li class="divider"></li>';
                            } elseif ($child->action == 'nav-header') {
                                echo '<li class="nav-header">' . $child->name . '</li>';
                            } else {
                            	if(RC_Uri::current_url() === $child->link){
                            		echo '<li class="active"><a href="' . $child->link . '">' . $child->name . '</a></li>';
                            	}else {
                            		echo '<li><a href="' . $child->link . '">' . $child->name . '</a></li>';
                            	}
                            }
                        }
                    }
                    echo '</ul>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
        }
    }

    public static function display_admin_copyright() {
    	$company_msg  = __('版权所有 © 2013-2016 上海商创网络科技有限公司，并保留所有权利。');
    	$ecjia_icon   = RC_Uri::admin_url('statics/images/ecjia_icon.png');

        echo "<div class='row-fluid footer'>
        		<div class='span12'>
        			<span class='f_l w80'>
        				<img src='{$ecjia_icon}' />
        			</span>
        			{$company_msg}
        		</div>
        	</div>";
    }

    public static function is_super_admin() {

    }
}

// end