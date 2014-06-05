<?php
//申请到的appid
$_SESSION["appid"]    = 100374329; 

//申请到的appkey
$_SESSION["appkey"]   = "e84a602786a66b52579d8b0d3ac23182"; 

//QQ登录成功后跳转的地址,请确保地址真实可用，否则会导致登录失败。
$_SESSION["callback"] = "http://".$_SERVER["SERVER_NAME"]."/user/qq_callback.html"; 

//QQ授权api接口.按需调用
$_SESSION["scope"] = "get_user_info,add_share,list_album,add_album,upload_pic,add_topic,add_one_blog,add_weibo";
?>
