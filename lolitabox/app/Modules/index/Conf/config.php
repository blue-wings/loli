<?php


return array(
		/* 定义模板定界符 */
		'TMPL_L_DELIM' => '<{',
		'TMPL_R_DELIM' => '}>',
		'TMPL_ACTION_ERROR' => 'public:error', // 默认错误跳转对应的模板文件
		'TMPL_ACTION_SUCCESS' => 'public:success', // 默认成功跳转对应的模板文件
		'URL_HTML_SUFFIX' => 'html', // 扩展名为.html
		'PAGE'=>array(
				'theme'=>'%first% %upPage% %linkPage% %downPage% %end% %ajax% '
		),
		/* Cookie设置 */
		'COOKIE_EXPIRE' => 30 * 24 * 3600, // Coodie有效期30天
		'COOKIE_DOMAIN' => $_SERVER["SERVER_NAME"], // Cookie有效域名
		'COOKIE_PATH' => '/', // Cookie路径
		'COOKIE_PREFIX' => 'loli_', // Cookie前缀 避免冲突
		'COOKIE_AUTHKEY' => 'AJSIFKJSI22AHSDHFALSKD', // [自定义]
		'COOKIE_AUTHKEY_SPLIT' => '|||', // [自定义]	
		/*表单TOKEN设置*/
		'TOKEN_ON' => true, // 是否开启令牌验证
		'TOKEN_NAME' => '__hash__', // 令牌验证的表单隐藏字段名称
		'TOKEN_TYPE' => 'md5', // 令牌哈希验证规则 默认为MD5
		'TOKEN_RESET' => true, // 令牌验证出错后是否重置令牌 默认为true
);