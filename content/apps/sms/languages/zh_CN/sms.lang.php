<?php
  
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * ECJIA 短信模块语言文件
 */
return array(
	/* 导航条 */
	'register_sms' 	=> '注册或启用短信账号',
	'sms_deliver' 	=> '商家发货通知模板',
	'sms_order' 	=> '客户下单通知模板',
	'sms_money' 	=> '客户付款通知模板',
	
	/* 注册和启用短信功能 */
	'email' 		=> '电子邮箱',
	'password' 		=> '登录密码',
	'domain' 		=> '网店域名',
	'register_new' 	=> '注册新账号',
	'error_tips' 	=> '请在商店设置->短信设置，先注册短信服务并正确配置短信服务！',
	'enable_old' 	=> '启用已有账号',
	
	/* 短信特服信息 */
	'sms_user_name' 	=> '用户名：',
	'sms_password' 		=> '密码：',
	'sms_domain' 		=> '域名：',
	'sms_num' 			=> '短信特服号：',
	'sms_count' 		=> '发送短信条数：',
	'sms_total_money' 	=> '总共冲值金额：',
	'sms_balance' 		=> '余额：',
	'sms_last_request' 	=> '最后一次请求时间：',
	'disable' 			=> '注销短信服务',
	
	/* 发送短信 */
	'phone' 		=> '接收手机号码',
	'user_rand'		=> '按用户等级发送短消息',
	'phone_notice'	=> '多个手机号码用半角逗号分开',
	'msg' 			=> '消息内容',
	'msg_notice' 	=> '最长70字符',
	'send_date' 	=> '定时发送时间',
	'send_date_notice' 		=> '格式为YYYY-MM-DD HH:II。为空表示立即发送。',
	'back_send_history' 	=> '返回发送历史列表',
	'back_charge_history'	=> '返回充值历史列表',
	
	/* 记录查询界面 */
	'start_date' 	=> '开始日期',
	'date_notice' 	=> '格式为YYYY-MM-DD。可为空。',
	'end_date' 		=> '结束日期',
	'page_size' 	=> '每页显示数量',
	'page_size_notice' => '可为空，表示每页显示20条记录',
	'page' 			=> '页数',
	'page_notice' 	=> '可为空，表示显示1页',
	'charge' 		=> '请输入您想要充值的金额',
	
	/* 动作确认信息 */
	'history_query_error' => '对不起，在查询过程中发生错误。',
	'enable_ok' 	=> '恭喜，您已成功启用短信服务！',
	'enable_error' 	=> '对不起，您启用短信服务失败。',
	'disable_ok' 	=> '您已经成功注销短信服务。',
	'disable_error' => '注销短信服务失败。',
	'register_ok' 	=> '恭喜，您已成功注册短信服务！',
	'register_error'=> '对不起，您注册短信服务失败。',
	'send_ok' 		=> '恭喜，您的短信已经成功发送！',
	'send_error' 	=> '对不起，在发送短信过程中发生错误。',
	'error_no' 		=> '错误标识',
	'error_msg' 	=> '错误描述',
	'empty_info' 	=> '您的短信特服信息为空。',
	
	/* 充值记录 */
	'order_id' 	=> '订单号',
	'money' 	=> '充值金额',
	'log_date' 	=> '充值日期',
	
	/* 发送记录 */
	'sent_phones' 	=> '发送手机号码',
	'content' 		=> '发送内容',
	'charge_num' 	=> '计费条数',
	'sent_date' 	=> '发送日期',
	'send_status' 	=> '发送状态',
	
	'status' => array(
		0 => '失败',
		1 => '成功',
	),
	
	'user_list' 	=> '全体会员',
	'please_select' => '请选择会员等级',
		
	/* 提示 */
	'test_now' 	=> '<span style="color:red,"></span>',
	'msg_price' => '<span style="color:green,">短信每条0.1元(RMB)</span>',
		
	/* API返回的错误信息 */
	'api_errors' => array(
		//--注册
		'register' => array(
			1 => '域名不能为空。',
			2 => '邮箱填写不正确。',
			3 => '用户名已存在。',
			4 => '未知错误。',
			5 => '接口错误。',
		),
		//--获取余额
		'get_balance' => array(
			1 => '用户名密码不正确。',
			2 => '用户被禁用。',
		),
		'send' => array(
			//--发送短信
			1 => '用户名密码不正确。',
			2 => '短信内容过长。',
			3 => '发送日期应大于当前时间。',
			4 => '错误的号码。',
			5 => '账户余额不足。',
			6 => '账户已被停用。',
			7 => '接口错误。',
		),
		'get_history' => array(
			//--历史记录
			1 => '用户名密码不正确。',
			2 => '查无记录。'
		),
		'auth' => array(
			//--用户验证
			1 => '密码错误。',
			2 => '用户不存在。',
		)
	),
		
	/* 用户服务器检测到的错误信息 */
	'server_errors' => array(
		1 	=> '注册信息无效。',		//ERROR_INVALID_REGISTER_INFO
		2 	=> '启用信息无效。',		//ERROR_INVALID_ENABLE_INFO
		3 	=> '发送的信息有误。',	//ERROR_INVALID_SEND_INFO
		4 	=> '填写的查询信息有误。',	//ERROR_INVALID_HISTORY_QUERY
		5 	=> '无效的身份信息。',	//ERROR_INVALID_PASSPORT
		6 	=> 'URL不对。',			//ERROR_INVALID_URL
		7 	=> 'HTTP响应体为空。',	//ERROR_EMPTY_RESPONSE
		8 	=> '无效的XML文件。',		//ERROR_INVALID_XML_FILE
		9 	=> '无效的节点名字。',	//ERROR_INVALID_NODE_NAME
		10 	=> '存储失败。',			//ERROR_CANT_STORE
		11 	=> '短信功能尚未激活。',	//ERROR_INVALID_PASSPORT
	),
		
	/* 客户端JS语言项 */
	//--注册或启用
	'js_languages' => array(
		'password_empty_error' 	=> '密码不能为空。',
		'username_empty_error' 	=> '用户名不能为空。',
		'username_format_error' => '用户名格式不对。',
		'domain_empty_error' 	=> '域名不能为空。',
		'domain_format_error' 	=> '域名格式不对。',
		'send_empty_error' 		=> '发送手机号与发送等级至少填写一项！',
		//--发送
		'phone_empty_error' 	=> '请填写手机号。',
		'phone_format_error' 	=> '手机号码格式不对。',
		'msg_empty_error' 		=> '请填写消息内容。',
		'send_date_format_error'=> '定时发送时间格式不对。',
		//--历史记录
		'start_date_format_error' 	=> '开始日期格式不对。',
		'end_date_format_error' 	=> '结束日期格式不对。',
		//--充值
		'money_empty_error' 	=> '请输入您要充值的金额。',
		'money_format_error' 	=> '金额格式不对。',
	),
	
	//追加
	'sms_config'		=> '短信配置',
	'set_config'		=> '短信管理>短信配置',
	'update_success'	=> '更新短信配置成功！',
	'surplus'			=> '您当前短信还剩余 %s 条',
	'platform_config'	=> '平台配置',
	'label_sms_account'	=> '短信平台帐号：',
	'label_sms_password'=> '短信平台密码：',
	'search_balance'	=> '查询账户余额',
	'label_shop_mobile'	=> '商家电话：',
	
	'label_config_order'		=> '客户下单：',
	'label_config_money'		=> '客户付款：',
	'label_config_shipping'		=> '商家发货：',
	'label_config_user'			=> '用户注册：',
	'sms_receipt_code'			=> '收货验证码：',
	'send_sms'					=> '发短信',
	'not_send_sms'				=> '不发短信',
	'config_order_notice' 		=> '客户下订单时是否给商家发短信',
	'config_money_notice'		=> '客户付款时是否给商家发短信',
	'config_shipping_notice'	=> '商家发货时是否给客户发短信',
	'config_user_notice'		=> '用户注册时是否给客户发短信',
	'receipt_code_notice'		=> '用户付款后是否给客户发收货验证码',
	
	'sms_template'			=> '短信模板',
	'sms_template_list'		=> '短信模板列表',
	'add_sms_template'		=> '添加短信模版',
	'template_name_exist'	=> '该短信模板的名称已经存在！',
	'template_code_is'		=> '模板名是 %s',
	'template_subject_is'	=> '短信主题是 %s',
	'return_template_list'	=> '返回短信模版列表',
	'continue_add_template'	=> '继续添加短信模版',
	'add_template_success'	=> '添加短信模版成功',
	'edit_sms_template'		=> '编辑短信模版',
	'edit_template_success'	=> '编辑短信模版成功',
	'remove_template_success' => '删除短信模版成功',
	
	'label_sms_template'	=> '模版名称：',
	'label_subject'			=> '短信主题：',
	'label_content'			=> '模板内容：',
	'update'				=> '更新',
	'sms_template_code'		=> '模板名称',
	'sms_template_subject'	=> '短信主题',
	'sms_template_content'	=> '模板内容',
	'drop_confirm'			=> '您确定要删除该短信模板吗？',
	
	'sms_record'			=> '短信记录',
	'sms_record_list'		=> '短信记录列表',
	'send_sms'				=> '发送短信',
	'add_sms_send'			=> '添加短信发送',
	'receive_number_is'		=> '再次发送短信，接收号码是 %s',
	'send_success'			=> '发送成功！',
	'batch_send_success'	=> '已批量发送完毕',
	'label_user_rank'		=> '按用户等级发送短消息：',
	'select_user_rank'		=> '请选择会员等级',
	'label_send_num'		=> '接收手机号码：',
	'send_num_notice'		=> '多个手机号码用半角逗号分开',
	'label_msg'				=> '消息内容：',
	
	'all'			=> '全部',
	'wait_send'		=> '待发送',
	'send_success'	=> '发送成功',
	'send_faild'	=> '发送失败',
	'batch_handle'	=> '批量操作',
	
	'batch_send_confirm'	=> '您确定要再次发送选中的短信记录吗？',
	'select_confirm'		=> '请先选中要再次发送的短信记录！',
	'send_sms_again'		=> '再次发送短信',
	
	'start_time'	=> '开始时间',
	'to'			=> '至',
	'end_time'		=> '截止时间',
	'filter'		=> '筛选',
	'sms_keywords'	=> '请输入短信接收号码或内容关键字',
	'search'		=> '搜索',
	'sms_number'	=> '接收短信号码',
	'sms_content'	=> '短信内容',
	'send_time'		=> '发送时间',
	'send_status'	=> '发送状态',
	'error_times'	=> '次发送错误',
	'send_again'	=> '再次发送',
	
	'not_found_smsid'	=> '没有找到此短信记录',
	'invalid_argument'	=> '无效参数',
	'invalid_account'	=> '无效的帐号信息',
	
	'sms_manage'			=> '短信管理',
	'sms_send_manage'		=> '短信发送管理',
	'sms_history_manage'	=> '短信历史记录管理',
	'sms_template_manage'	=> '短信模板管理',
	'sms_config_manage'		=> '短信配置管理',
	'batch_setup'			=> '批量设置',
	
	'overview'				=> '概述',
	'more_info'     		=> '更多信息：',
	
	'sms_config_help'		=> '欢迎访问ECJia智能后台短信配置页面，系统中有关短信配置信息显示在此页面。',
	'about_sms_config'		=> '关于短信配置帮助文档',
	
	'sms_template_help'		=> '欢迎访问ECJia智能后台短信模板页面，系统中所有的短信模板都会显示在此列表中。',
	'about_sms_template'	=> '关于短信模板帮助文档',
	
	'add_template_help'		=> '欢迎访问ECJia智能后台添加短信模板页面，可以在此添加短信模板。',
	'about_add_template'	=> '关于添加短信模板帮助文档',
	
	'edit_template_help'	=> '欢迎访问ECJia智能后台编辑短信模板页面，可以在此编辑相应的短信模板信息。',
	'about_edit_template'	=> '关于编辑短信模板帮助文档',
	
	'sms_history_help'		=> '欢迎访问ECJia智能后台短信记录列表页面，系统中所有的短信记录都会显示在此列表中。',
	'about_sms_history'		=> '关于短信记录列表帮助文档',
	
	'js_lang' => array(
		'sms_user_name_required'	=> '请填写短信平台账号！',
		'sms_password_required'		=> '请输入短信平台密码！',
		'sms_password_minlength'	=> '短信平台密码长度不能小于6！',
		
		'sFirst'					=> '首页',
		'sLast' 					=> '尾页',
		'sPrevious'					=> '上一页',
		'sNext'						=> '下一页',
		'sInfo'						=> '共_TOTAL_条记录 第_START_条到第_END_条',
		'sZeroRecords' 				=> '没有找到任何记录',
		'sEmptyTable' 				=> '没有找到任何记录',
		'sInfoEmpty'				=> '共0条记录',
		'sInfoFiltered'				=> '（从_MAX_条数据中检索）',
		
		'template_code_required'	=> '短信模板名称不能为空！',
		'subject_required'			=> '短信主题不能为空！',
		'content_required'			=> '模板内容不能为空！',
		
		'start_lt_end_time'			=> '开始时间不得大于结束时间！',
		'send_num_required'			=> '请填写接收手机号码！',
		'msg_required'				=> '请填写消息内容！',
	),
	
	//追加
	'sms_template_update'	=> '短信模板更新',
	'sms_template_delete'	=> '短信模板删除',
	'sms_config_update' 	=> '更新短信配置'
);

//end