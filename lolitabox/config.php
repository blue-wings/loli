<?php
define('PROJECT_NAME','萝莉盒');
//项目目录<根路径>
define('PROJECT_ROOT_PATH', dirname(__FILE__));
//项目域名常量
define('PROJECT_URL_ROOT',"http://".$_SERVER["SERVER_NAME"]."/");
//定义网站的根目录(siteadmin的上一级)
define('PROJECT_DIR_ROOT',realpath("./"));
//支付宝SDK目录
define('ALIPAY_ROOT',PROJECT_ROOT_PATH.DIRECTORY_SEPARATOR."framework".DIRECTORY_SEPARATOR."pay".DIRECTORY_SEPARATOR);
//SINA第三方登录SDK目录
define('SINA_OPEN_ROOT',PROJECT_ROOT_PATH.DIRECTORY_SEPARATOR."framework".DIRECTORY_SEPARATOR."open".DIRECTORY_SEPARATOR."sina".DIRECTORY_SEPARATOR);
//QQ第三方登录SDK目录
define('QQ_OPEN_ROOT',PROJECT_ROOT_PATH.DIRECTORY_SEPARATOR."framework".DIRECTORY_SEPARATOR."open".DIRECTORY_SEPARATOR."qq".DIRECTORY_SEPARATOR);
define('QQ_OPEN_CLASS_ROOT',PROJECT_ROOT_PATH.DIRECTORY_SEPARATOR."framework".DIRECTORY_SEPARATOR."open".DIRECTORY_SEPARATOR."qq".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR);
//DATA物理保存路径
define('DATA_DIR_ROOT',PROJECT_ROOT_PATH.DIRECTORY_SEPARATOR."data");
//用户数据物理保存路径
define('USER_DATA_DIR_ROOT',PROJECT_ROOT_PATH.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."userdata");
//文章信息图片数据物理保存路径
define('ARTICLE_ATTATCH_DIR_ROOT',DATA_DIR_ROOT.DIRECTORY_SEPARATOR."article".DIRECTORY_SEPARATOR);
//文章信息图片数据URL访问路径
define('ARTICLE_ATTATCH_URL_ROOT',PROJECT_URL_ROOT."data/article/");
//支付宝支付日志数据物理保存路径
define('PAY_LOG_DIR_ROOT',DATA_DIR_ROOT.DIRECTORY_SEPARATOR."paylogs".DIRECTORY_SEPARATOR."alipay");
//产品图片物理保存路径
define('PRODUCTIMG_DIR_ROOT',DATA_DIR_ROOT.DIRECTORY_SEPARATOR."productimg/");
//产品图片URL访问路径
define('PRODUCTIMG_URL_ROOT',PROJECT_URL_ROOT."data/productimg/");
//分词字典目录
define('SCWS_ROOT',PROJECT_ROOT_PATH.DIRECTORY_SEPARATOR."framework".DIRECTORY_SEPARATOR."scws".DIRECTORY_SEPARATOR);
//config_global.inc.php路径定义
define('CONFIG_GLOBAL_FILE',PROJECT_DIR_ROOT.DIRECTORY_SEPARATOR."app".DIRECTORY_SEPARATOR."Conf".DIRECTORY_SEPARATOR."C.inc.php");
//config_db.inc.php路径定义
define('CONFIG_DB_FILE',PROJECT_DIR_ROOT.DIRECTORY_SEPARATOR."app".DIRECTORY_SEPARATOR."Conf".DIRECTORY_SEPARATOR."CDB.inc.php");
?>