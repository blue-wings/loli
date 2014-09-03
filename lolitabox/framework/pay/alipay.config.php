<?php
/* *
 * 配置文件
 * 版本：3.2
 * 日期：2011-03-25
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
	
 * 提示：如何获取安全校验码和合作身份者id
 * 1.用您的签约支付宝账号登录支付宝网站(www.alipay.com)
 * 2.点击“商家服务”(https://b.alipay.com/order/myorder.htm)
 * 3.点击“查询合作者身份(pid)”、“查询安全校验码(key)”
	
 * 安全校验码查看时，输入支付密码后，页面呈灰色的现象，怎么办？
 * 解决方法：
 * 1、检查浏览器配置，不让浏览器做弹框屏蔽设置
 * 2、更换浏览器或电脑，重新登录查询。
 */
 
//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//合作身份者id，以2088开头的16位纯数字
$aliapy_config['partner']      = '2088701849713532';
$aliapy_config['key']          = 'x60fvb27v9mv7j4kuxxwfi6zfcui0ohb';
$aliapy_config['seller_email'] = 'lolitabox2012@163.com';
$aliapy_config['return_url']   = "http://".$_SERVER["SERVER_NAME"]."/pay/alipay_return.html";
$aliapy_config['notify_url']   = "http://".$_SERVER["SERVER_NAME"]."/pay/alipay_notify.html";
$aliapy_config['safeKeeping_return_url']   = "http://".$_SERVER["SERVER_NAME"]."/safeKeepingPay/alipay_return.html";
$aliapy_config['safeKeeping_notify_url']   = "http://".$_SERVER["SERVER_NAME"]."/safeKeepingPay/alipay_notify.html";
$aliapy_config['return_member_url']   = "http://".$_SERVER["SERVER_NAME"]."/memberPay/alipay_return.html";
$aliapy_config['notify_member_url']   = "http://".$_SERVER["SERVER_NAME"]."/memberPay/alipay_notify.html";
$aliapy_config['sign_type']    = 'MD5';
$aliapy_config['input_charset']= 'utf-8';
$aliapy_config['transport']    = 'http';
?>