<?php
/**
 * LOLITABOX前台config文件
 * @author zhenghong@lolitabox.com
 * 2013-01-14
 */
$config_db = require_once(CONFIG_DB_FILE); // 加载数据库配置参数
$config_global=require_once(CONFIG_GLOBAL_FILE); //加载全局配置参数
$constants = require_once(CONFIG_CONSTANTS_FILE);
$array = array(
	/* 定义URL模式*/
	'TMPL_CACHE_ON'=>false,
	'SHOW_PAGE_TRACE'        =>true, //上线前删除
    'APP_GROUP_LIST'            =>  'index,admin,api,m,wx,op',   //分组
    'DEFAULT_GROUP'             =>  'index',
    'APP_GROUP_MODE'            =>  1,
	'URL_MODEL' => '2', // 使用rewrite规则
	'TOKEN_ON' => true, // 是否开启令牌验证
	'TOKEN_NAME' => '__hash__', // 令牌验证的表单隐藏字段名称
	'TOKEN_TYPE' => 'md5', // 令牌哈希验证规则 默认为MD5
	'TOKEN_RESET' => true, // 令牌验证出错后是否重置令牌 默认为true
	
	/* Cookie设置 */
	'COOKIE_EXPIRE' => 30 * 24 * 3600, // Coodie有效期30天
	'COOKIE_DOMAIN' => $_SERVER["SERVER_NAME"], // Cookie有效域名
	'COOKIE_PATH' => '/', // Cookie路径
	'COOKIE_PREFIX' => 'loli_', // Cookie前缀 避免冲突
	'COOKIE_AUTHKEY' => 'AJSIFKJSI22AHSDHFALSKD', // [自定义]
	'COOKIE_AUTHKEY_SPLIT' => '|||', // [自定义]
	'ERROR_PAGE' =>'/error.html',
	'LOG_RECORD'			=>	true,  // 进行日志记录
    'LOG_EXCEPTION_RECORD'  => 	true,    // 是否记录异常信息日志
    'LOG_LEVEL'       		=>  'EMERG,ALERT,CRIT,ERR,WARN,NOTICE,INFO',  // 允许记录的日志级别
	'TAG_NESTED_LEVEL'		=> 	5
);
return array_merge ( $config_db,$config_global,$constants,$array);
?>