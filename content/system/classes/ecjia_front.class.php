<?php
  
/**
 * ecjia 前端页面控制器父类
 */
defined('IN_ECJIA') or exit('No permission resources.');

abstract class ecjia_front extends ecjia_base implements ecjia_template_fileloader {
    
	public function __construct() {
		parent::__construct();
		
		self::$controller = static::$controller;
		self::$view_object = static::$view_object;
	
		if (defined('DEBUG_MODE') == false) {
			define('DEBUG_MODE', 0);
		}

		/* 商店关闭了，输出关闭的消息 */
		if (ecjia::config('shop_closed') == 1) {
		    RC_Hook::do_action('ecjia_shop_closed');
		}
		
		/* session id 定义*/
		defined('SESS_ID') or define('SESS_ID', RC_Session::session()->get_session_id());
		RC_Hook::do_action('ecjia_front_access_session');
		
		if (isset($_SERVER['PHP_SELF'])) {
			$_SERVER['PHP_SELF'] = htmlspecialchars($_SERVER['PHP_SELF']);
		}

		RC_Response::header('Cache-control', 'private');
		
		//title信息
		$this->assign_title();
		RC_Hook::do_action('ecjia_compatible_process');
		
		if (RC_Config::get('system.debug')) {
			error_reporting(E_ALL);
		} else {
			error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
		}

		/* 判断是否支持 Gzip 模式 */
		if (RC_Config::get('system.gzip') && RC_Env::gzip_enabled()) {
			ob_start('ob_gzhandler');
		} else {
			ob_start();
		}
		
		RC_Hook::do_action('ecjia_front_finish_launching');
	}
	
	protected function session_start()
	{
	    RC_Hook::add_filter('royalcms_session_name', function ($sessin_name) {
		    return RC_Config::get('session.session_name');
		});
	    
	    RC_Hook::add_filter('royalcms_session_id', function ($sessin_id) {
	        return RC_Hook::apply_filters('ecjia_front_session_id', $sessin_id);
	    });
	             
        RC_Session::start();
	}
	
	public function create_view()
	{
	    $view = new ecjia_view($this);
	    
	    // 模板目录
	    $view->setTemplateDir(SITE_THEME_PATH . RC_Theme::get_template() . DIRECTORY_SEPARATOR);
	    // 添加主题插件目录
	    $view->addPluginsDir(SITE_THEME_PATH . RC_Theme::get_template() . DIRECTORY_SEPARATOR . 'smarty' . DIRECTORY_SEPARATOR);
	    // 编译目录
	    $view->setCompileDir(TEMPLATE_COMPILE_PATH . 'front' . DIRECTORY_SEPARATOR);
	    
	    if (RC_Config::get('system.debug')) {
	        $view->caching = Smarty::CACHING_OFF;
	        $view->cache_lifetime = 0;
	        $view->debugging = true;
	        $view->force_compile = true;
	    } else {
	        $view->caching = Smarty::CACHING_LIFETIME_CURRENT;
	        $view->cache_lifetime = ecjia::config('cache_time');
	        $view->debugging = false;
	        $view->force_compile = false;
	    }
	    
	    $view->assign('ecjia_charset', RC_CHARSET);
	    $view->assign('theme_url', RC_Theme::get_template_directory_uri() . '/');
	    $view->assign('system_static_url', RC_Uri::system_static_url() . '/');

	    try 
	    {
	        $css_path = Ecjia_ThemeManager::driver(ecjia::config(Ecjia_ThemeManager::getTemplateName()))->loadSpecifyStyle(Ecjia_ThemeManager::getStyleName())->getStyle();
	        $view->assign('theme_css_path', $css_path);
	    } 
	    catch (InvalidArgumentException $e)
	    {
	        //
	    }
	    
	    return $view;
	}
	
	/**
	 * 获得前台模板目录
	 * @return string
	 */
	public function get_template_dir()
	{	    
	    $style = RC_Theme::get_template();
	    
	    $dir = SITE_THEME_PATH . $style . DIRECTORY_SEPARATOR;
	
	    return $dir;
	}
	
	/**
	 * 获得前台模版文件
	 */
	public function get_template_file($file)
	{
	    $style = RC_Theme::get_template();
	
	    if (is_null($file)) {
	        $file = SITE_THEME_PATH . $style . DIRECTORY_SEPARATOR . ROUTE_M . DIRECTORY_SEPARATOR . ROUTE_C . '_' . ROUTE_A;
	    } elseif (! RC_File::is_absolute_path($file)) {
	        $file = SITE_THEME_PATH . $style . DIRECTORY_SEPARATOR . $file;
	    }
	
	    // 添加模板后缀
	    if (! preg_match('@\.[a-z]+$@', $file))
	        $file .= RC_Config::get('system.tpl_fix');
	
	    // 将目录全部转为小写
	    if (is_file($file)) {
	        return $file;
	    } else {
	        // 模版文件不存在
	        if (RC_Config::get('system.debug'))
	            // TODO:
	            rc_die("Template does not exist.:$file");
	        else
	            return null;
	    }
	}
	
	public final function display($tpl_file = null, $cache_id = null, $show = true, $options = array()) {
	    if (strpos($tpl_file, 'string:') !== 0) {
	        if (RC_File::file_suffix($tpl_file) !== 'php') {
	            $tpl_file = $tpl_file . '.php';
	        }
	        if (RC_Config::get('system.tpl_usedfront') && ! RC_File::is_absolute_path($tpl_file)) {
	            $tpl_file = ecjia_app::get_app_template($tpl_file, ROUTE_M, false);
	        }
	    }
		return parent::display($tpl_file, $cache_id, $show, $options);
	}
	
	public final function fetch($tpl_file = null, $cache_id = null, $options = array()) {
	    if (strpos($tpl_file, 'string:') !== 0) {
	        if (RC_File::file_suffix($tpl_file) !== 'php') {
	            $tpl_file = $tpl_file . '.php';
	        }
	        if (RC_Config::get('system.tpl_usedfront') && ! RC_File::is_absolute_path($tpl_file)) {
	            $tpl_file = ecjia_app::get_app_template($tpl_file, ROUTE_M, false);
	        }
	    }
		return parent::fetch($tpl_file, $cache_id, $options);
	}
	
	/**
	 * 判断是否缓存
	 *
	 * @access  public
	 * @param   string     $tpl_file
	 * @param   sting      $cache_id
	 *
	 * @return  bool
	 */
	public final function is_cached($tpl_file, $cache_id = null, $options = array()) {
	    if (strpos($tpl_file, 'string:') !== 0) {
	        if (RC_File::file_suffix($tpl_file) !== 'php') {
	            $tpl_file = $tpl_file . '.php';
	        }
	        if (RC_Config::get('system.tpl_usedfront')) {
	            $tpl_file = ecjia_app::get_app_template($tpl_file, ROUTE_M, false);
	        }
	    }
	    
	    $is_cached = parent::is_cached($tpl_file, $cache_id, $options);
	    
	    $purge = royalcms('request')->query('purge', 0);
	    $purge = intval($purge);
	    if ($is_cached && $purge === 1) {
	        parent::clear_cache($tpl_file, $cache_id, $options);
	        return false;
	    }
	    return $is_cached;
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
	    $front_tpl = SITE_THEME_PATH . RC_Config::get('system.tpl_style') . DIRECTORY_SEPARATOR . RC_Config::get('system.tpl_message');
	
	    if ($tpl) {
	        $this->assign(array(
	            'msg' => $msg,
	            'url' => $url,
	            'time' => $time
	        ));
	        $tpl = SITE_THEME_PATH . RC_Config::get('system.tpl_style') . DIRECTORY_SEPARATOR . $tpl;
	        $this->display($tpl);
	    } elseif (file_exists($front_tpl)) {
	        $this->assign(array(
	            'msg' => $msg,
	            'url' => $url,
	            'time' => $time
	        ));
	        $this->display($front_tpl);
	    } else {
	        return parent::message($msg, $url, $time, $tpl);
	    }
	
	    exit(0);
	}
	
	
	/**
	 * 向模版注册title
	 */
	public function assign_title($title = '') {
	    $title_suffix = RC_Hook::apply_filters('page_title_suffix', ' ');
	    if (empty($title)) {
	        $this->assign('page_title', ecjia::config('shop_title') . $title_suffix);
	    } else {
	        $this->assign('page_title', $title . '-' . ecjia::config('shop_title') . $title_suffix);
	    }
	}
	
	public function assign_template($ctype = '', $catlist = array()) {
		$this->assign('image_width',   ecjia::config('image_width'));
		$this->assign('image_height',  ecjia::config('image_height'));
		$this->assign('points_name',   ecjia::config('integral_name'));
		$this->assign('qq',            explode(',', ecjia::config('qq')));
		$this->assign('ww',            explode(',', ecjia::config('ww')));
		$this->assign('ym',            explode(',', ecjia::config('ym')));
		$this->assign('msn',           explode(',', ecjia::config('msn')));
		$this->assign('skype',         explode(',', ecjia::config('skype')));
		$this->assign('stats_code',    ecjia::config('stats_code'));
		$this->assign('copyright',     sprintf(RC_Lang::get('system::system.copyright'), date('Y'), ecjia::config('shop_name')));
		$this->assign('shop_name',     ecjia::config('shop_name'));
		$this->assign('service_email', ecjia::config('service_email'));
		$this->assign('service_phone', ecjia::config('service_phone'));
		$this->assign('shop_address',  ecjia::config('shop_address'));
		$this->assign('ecs_version',   VERSION);
		$this->assign('icp_number',    ecjia::config('icp_number'));
		$this->assign('username',      !empty($_SESSION['user_name']) ? $_SESSION['user_name'] : '');
	
		if (ecjia::config('search_keywords', ecjia::CONFIG_CHECK)) {
			$searchkeywords = explode(',', trim(ecjia::config('search_keywords')));
			$this->assign('searchkeywords', $searchkeywords);
		}
	}
	

	protected function load_hooks() {
		RC_Hook::add_action( 'front_head',	'front_enqueue_scripts',	1 );
		RC_Hook::add_action( 'front_head',	'front_print_styles',		8 );
		RC_Hook::add_action( 'front_head',	'front_print_head_scripts',	9 );
		RC_Hook::add_action( 'front_footer',	'front_print_footer_scripts', 20 );
		RC_Hook::add_action( 'front_print_footer_scripts', '_front_footer_scripts');
		
		$apps = ecjia_app::installed_app_floders();
		if (is_array($apps)) {
			foreach ($apps as $app) {
				RC_Loader::load_app_class('hooks.front_' . $app, $app, false);
			}
		}
	}
	
}

// end