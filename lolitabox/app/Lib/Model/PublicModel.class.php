<?php
//公共模型
class PublicModel extends Model {
	
	public $smilies=array("闭嘴"=>"bizui","拜拜"=>"bye","吃货"=>"eat","害羞"=>"haixiu","汗"=>"han","呵呵"=>"hehe","花心"=>"huaxin","互粉"=>"hufen","激动"=>"jidong","囧"=>"jiong","哭"=>"ku","大笑"=>"laugh","咒骂"=>"ma","抛媚眼"=>"meiyan","亲亲"=>"qin","衰"=>"shuai","无奈"=>"wunai","点头"=>"yes","晕"=>"yun","赞"=>"zan");

	
	
	/**
	 * 将一段内容中的表情符号解析成图片
	 * @param $content 要处理的内容
	 * @author penglele
	 */
	public function getContentSmilies($content){
		$content=trim($content);
		if(!empty($content)){
			$pattern =  "/[[]([\x{4e00}-\x{9fa5}]+)[]]/u";
			preg_match_all($pattern,$content,$arr);
			$arr=array_unique($arr[1]);
			if($arr){
				$smiles=$this->smilies;
				$smile_key=array_keys($smiles);
				foreach($arr as $value){
					if(in_array($value, $smile_key)){
						$new_val="/[[](".$value.")[]]/";
						$replacement="<img src='./public/lolitabox/smilies/".$smiles[$value].".gif' />";
						$content=preg_replace($new_val, $replacement, $content);
					}
				}
			}
		}
		return $content;
	}
	
	/**
	 * 对分享内容处理---对@加链接
	 * @param string $content 要处理的内容
	 * @return string $content
	 * @author penglele
	 */
	public function handleShareContent($content){
		$content=$content." ";
		$content = str_replace('"',"'",$content);
		$pattern = '/@[^@|^ ]+ /';
		preg_match_all ( $pattern, $content, $arr );
		$arr=array_unique($arr[0]);
		$user_mod = D("Users");
		$products_mod = D("Products");
		$brand_mod = D("ProductsBrand");
		foreach($arr as $value){
			$nick_arr=explode("@",trim($value));
			$nickname=trim($nick_arr[1]);
			$userlist=$user_mod->getUserInfoByData(array('nickname'=>$nickname),"userid,is_solution");
			$userinfo=$userlist[0];
			$url ="";
			$re_nick="@".$nickname." ";
			if($userinfo!=false){
				$url = getSpaceUrl($userinfo['userid']);
				$content =str_replace($re_nick,"<a href='".$url."' class='WB_info bind_hover_card' target='_blank' bm_id='".$userinfo['userid']."'  bm_type='1'>@".$nickname."</a> ",$content);
				continue;
			}
			if($brandinfo = $brand_mod ->where("status=1")->getByName($nickname)){
				$url = getBrandUrl($brandinfo['id']);
				$content =str_replace($re_nick,"<a href='".$url."' class='WB_info bind_hover_card' target='_blank' bm_id='".$brandinfo['id']."'  bm_type='3'>@".$nickname."</a> ",$content);
				continue;
			}
			if($proinfo = $products_mod ->where("status=1")->getByPname($nickname)){
				$url = getProductUrl($proinfo['pid']);
				$content =str_replace($re_nick,"<a href='".$url."' class='WB_info bind_hover_card' target='_blank' bm_id='".$proinfo['pid']."'  bm_type='2'>@".$nickname."</a> ",$content);
				continue;
			}
		}
		
		//#
		$pattern = '/#[^\#|^ ]+#/';
		preg_match_all ( $pattern, $content, $array );
		foreach($array[0] as $key =>$val){
			$url=U("search/share",array("tag"=>trim($val,'#')));
			$content = str_replace($val,"<a href='".$url."' class='WB_info' target='_blank' bm_id='".$brandinfo['id']."'  bm_type='3'>".$val."</a> ",$content);
		}
		
		$content=$this->getContentSmilies($content);
		return $content;
	}

	/**
	 * 将一段内容中的表情符号删除
	 * @param $content 要处理的内容
	 * @param $type 是否对删除内容中的@，$type=0不删除，$type=1删除
	 * @author penglele
	 */
	public function deleteContentSmilies($content,$type=0){
		$content=trim($content);
		if(!empty($content)){
			$pattern =  "/[[]([\x{4e00}-\x{9fa5}]+)[]]/u";
			preg_match_all ( $pattern, $content, $arr );
			$arr=array_unique($arr[1]);
			if($arr){
				foreach($arr as $value){
					$smiles=$this->smilies;
					$smile_key=array_keys($smiles);
					if(in_array($value, $smile_key)){
						$new_val="/[[](".$value.")[]]/";
						$content=preg_replace($new_val, "", $content);
					}
				}
			}
			if($type!=0){
				$content=$this->deleteContentAt($content);
			}
		}
		return trim($content);
	}
	
	/**
	 * 删除一段内容中的@内容
	 * @param  $content 要处理的对象
	 */
	public function deleteContentAt($content){
		$content=$content." ";
		//$content = str_replace('"',"'",$content);
		$pattern = '/@[^@|^ ]+ /';
		preg_match_all ( $pattern, $content, $arr );
		$arr=array_unique($arr[0]);
		if($arr){
			foreach($arr as $value){
				$content=str_replace($value,"", $content);
			}
		}
		return trim($content);
	}
	
	
	/**
	 * 通过某个关键字获取所有资源集合
	 * @param unknown_type $tag
	 */
	public function getAtNameByTag($tag){
		$user_mod = M("Users");
		$products_mod = M("Products");
		$brand_mod = M("ProductsBrand");
		$list = $user_mod ->where("nickname like '%".$tag."%'")->field("userid as id,nickname")->limit(10)->select();
		if(count($list) < 10){
			$limit = 10-count($list);
			$list1 = $brand_mod ->where("name like '%".$tag."%' AND status=1")->field("id,name as nickname")->limit($limit)->select();
			$list = $list ? $list :array();
			$list1 = $list1 ? $list1: array();
			$list = array_merge($list,$list1);
			if(count($list) < 10){
				$limit = 10-count($list);
				$list2 = $products_mod ->where("pname like '%".$tag."%' AND status=1")->field("pid as id,pname as nickname")->limit($limit)->select();
				$list2 = $list2 ?$list2:array();
				$list = array_merge($list,$list2);
			}
			
		}
		return $list;
	}
	
	/**
	 * 获取@列表
	 * @param unknown_type $content
	 */
	public function getAtList($content,&$replace){
		$pattern = '/@[^@|^ ]+ /';
		$content=$content." ";
		preg_match_all ( $pattern, $content, $arr );
		$arr=array_unique($arr[0]);
		$user_mod = M("Users");
		$products_mod = M("Products");
		$brand_mod = M("ProductsBrand");
		$replace = "";
		foreach($arr as $value){
			$info=explode("@",trim($value));
			$tag=$info[1];
			$userinfo=$user_mod->getByNickname($tag);
			if($userinfo){
				if($userinfo['is_solution']==1){
					$list[4][]=$userinfo['userid'];
					continue;
				}else{
					$list[1][]=$userinfo['userid'];
					continue;
				}
				
			}
			$id = $brand_mod ->where("name='".$tag."' and status=1")->getField("id");
			if($id){
				$list[3][] =$id;
				continue;
			}
			$pid = $products_mod ->where("pname='".$tag."' and status=1")->getField("pid");
			if($pid){
				$list[2][] =$pid;
				continue;
			}
			
		}
		return $list;
	}
	
	/**
	 * 获取资源信息，包括用户，产品，品牌
	 * @param unknown_type $id
	 * @param unknown_type $type[1--用户。2--产品，3--品牌，4---解决方案]
	 */
	public function getSourceInfo($id,$type,$me=""){
		if(empty($id) || empty($type)){
			return false;
		}
		if($type==1 || $type==4){
			$userinfo = D("Users") ->getUserInfo($id);
			$info['userface'] =$userinfo['userface_65_65'];
			$info['userface_100_100'] =$userinfo['userface_100_100'];
			$info['nickname'] = $userinfo['nickname'];
			$info['fans_num'] = $userinfo['fans_num'];
			$info['spaceurl'] =getSpaceUrl($id);
			$info['userid'] = $id;
			$info['type'] = $type;
			$info['if_super'] = $userinfo['if_super'];
			$info['fansurl'] = U("solution_fans/".$id);
			$info['shareurl'] = U("solution/".$id);
			$info['description'] = $userinfo['description'];
			$info['status'] =D("Follow")->getUserFollowState($me,$id);
			$info['share_num'] = $userinfo['blog_num'];
			if($type ==1){
				$info['fansurl'] = getShortUrl("fans", $id);
				$info['shareurl'] = getShortUrl("share", $id);
				$info['province'] = $userinfo['province'];
				$info['skin'] = $userinfo['skin_property'];
				$info['sex'] = $userinfo['sex'];
				$info['sex_show'] = $userinfo['sex']==1 ? "帅锅":"妹纸";
				$info['sex_title'] = $userinfo['sex']==1 ? "男":"女";
				$info['sex_class'] =  $userinfo['sex']==1 ? "male":"female";
				
			}
			
				
		}else if($type==2){
			$pro_info=D("Products")->getProductInfo($id,$me,0);
			if($pro_info){
				$info['userface'] = $pro_info['pimg'];
				$info['userface_100_100']=$pro_info['pimg'];
				$info['nickname']=$pro_info['pname'];
				$info['fans_num']=$pro_info['fans_num'];
				$info['spaceurl']=getProductUrl($pro_info['pid']);
				$info['userid'] = $id;
				$info['share_num'] = $pro_info['evaluatenum'];
				$info['description'] = $pro_info['pintro'];
				$info['type'] = $type;
				$info['if_super'] = $pro_info['if_super'];
				$info['brandcid'] = $pro_info['brandcid'];
				$info['brandname'] = $pro_info['brandname'];
				$info['fansurl'] = U("products_fans/".$id);
				$info['shareurl'] = U("products_more/".$id);
				$info['status'] = $pro_info['type'];
				$effect=D("Products")->getProductEffectList($id);
				if($effect==false){
					$info['effectlist']="";
				}else{
					$info['effectlist']=$effect;
				}
			}
		}elseif($type==3){
			$brand_info=D("ProductsBrand")->getBrandInfo($id,'',$me,0);
			if($brand_info){
				$info['userface'] = $brand_info['logo_url'];
				$info['userface_100_100']=$brand_info['logo_url'];
				$info['nickname']=$brand_info['name'];
				$info['fans_num']=$brand_info['fans_num'];
				$info['spaceurl']=$brand_info['brandurl'];
				$info['userid'] = $id;
				$info['share_num'] = $brand_info['share_num'];
				$info['description'] = $brand_info['description'];
				$info['type'] = $type;
				$info['if_super'] = $brand_info['if_super'];
				$info['category'] = $brand_info['category'];
				$info['fansurl'] = U("brand_fans/".$id);
				$info['shareurl'] = U("brand/".$id);
				$info['area'] = $brand_info['area'];
				$info['status'] = $brand_info['type'];
			}
		}
		if(mb_strlen($info['description'],"utf8") > 40){
			$info['description'] = msubstr($info['description'], 0,40);
		}
		return $info;
	}
	
	
	public function getSmallPic($img,$imgkey=""){
		if(!$imgkey){
			$imgkey="img";
		}
		if(!file_exists(".".$img)){
			return false;
		}
		if(!$img){
			$array[$imgkey."_big"]="";//原图
			$array[$imgkey]="";//新缩略图
		}else{
			if(strpos($img, "http://")===false)
			{
				$pimg_arr=explode("/",$img);
				$count_pimg=count($pimg_arr);
				$pimg_name=$pimg_arr[$count_pimg-1]; //原图片名称
				$path_arr=explode($pimg_name,$img);//原图片路径$path_arr[0]
				$pimg_name_arr=explode(".",$pimg_name);
				$api_img=$pimg_name_arr[0]."_min".".".$pimg_name_arr[1];//api中图片名称
				$img_arr=explode(DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'userdata',USER_DATA_DIR_ROOT);
				$img_fp_target=$img_arr[0].$path_arr[0].$api_img;//新图片地址
				$old_img_fp_target=$img_arr[0].$img;
				//判断原图是否存在
				if(!file_exists($old_img_fp_target)){
					$array[$imgkey."_big"]="";//原图
					$array[$imgkey]="";//新缩略图
				}else{
					//如果原图存在，查看小图是否存在
					
					if(!file_exists($img_fp_target)){
						list($it_width, $it_height, $it_type, $it_attr)=getimagesize(".".$img);
						$new_width=200;
						if($it_width<=$new_width){
							$new_height=$it_height;
						}else{
							$new_height=$it_height*(200/$it_width);
						}
						import ( "ORG.Util.Image" );
						$img_fp_source=$img_arr[0].$array[$imgkey];
						copy($img_fp_source,$img_fp_target);
						Image::thumb ( $old_img_fp_target, $img_fp_target, "", $new_width, $new_height );
					}
					$array[$imgkey."_big"]=$img;//原图
					$array[$imgkey]=$path_arr[0].$api_img;//新缩略图
				}
			}else{
				return false;
			}
		}
		//Log::write ( $img . "==图片生成==", INFO );
		return $array;
	}
	
	/**
	 * 通过关键字联想
	 * @param int $tag
	 * @param int $type
	 * @param int $limit
	 */
	public function getSourceListByTag($tag,$type,$limit="10"){
		switch ($type){
			case 1:
				$list = M("Users")->field("userid as id,nickname as name")->where("nickname like '%".$tag."%'")->limit($limit)->select();
				break;
			case 2:
				$list = M("Products")->field("pid as id,pname as name")->where("pname like '%".$tag."%' AND status=1")->limit($limit)->select();
				break;
			case 3:
				$list = M("ProductsBrand")->field("id,name")->where("(name like '%".$tag."%' OR name_foreign like '%".$tag."%') AND status=1")->limit($limit)->select();
				break;
			case 4:
				$list = M("TagIndex")->field("tagid as id,tagname as name")->where("tagname like '%".$tag."%' AND status=1")->limit($limit)->order("totalnum DESC")->select();
				
		}
		return $list;
	}
	
	/**
	 * 匹配一段内容中是否含有空格、全角空格、@符号
	 * @param string $username
	 */
	public function checkThirdUsername($content){
		$content=trim($content);
		if(!empty($content)){
			$parten="/[\x{4e00}-\x{9fa5}|a-zA-Z0-9_]/u";
			preg_match_all($parten, $content, $arr);
			$content=implode("",$arr[0]);
		}
		if(empty($content)){
			$content=rand(1000,9999);
		}else{
			if(mb_strlen($content,"utf8") > 6){
				import("ORG.Util.String");
				$content=String::msubstr($content ,0,6,'utf-8',false);
			}else if(mb_strlen($content,"utf8")<2){
				$content=$content.rand(100,999);
			}
		}
		return $content;
	}
	
	/**
	 * 判断是否是内部访问用户
	 */
	public function checkUserIfInner(){
		$system_mod=M("SystemConfig");
		$key="LOLITABOX_REMOTE_IP";
		$info=$system_mod->getByKey($key);
		if(!$info)
			return false;
		if($_SERVER["REMOTE_ADDR"]!=$info['val'])
			return false;
		return true;
	}
	
	
	public function getShareNewSmallPic(){
		$pic_mod=M("UserShareAttach");
		$list=$pic_mod->field("id,imgpath")->order("id DESC")->where("id>30400")->select();
		foreach($list as $key=>$val){
			$new_img=$this->getSmallPic($val['imgpath']);
// 			$pic_mod->where("id=$val[id]")->save(array("imgpath"=>$new_img));
		}
		echo "success";
	}
	
	/**
	 * 获取date类型的时间的几天前或几天后的日期
	 * @param  $date
	 * @param $if_h 时间的形式是否为带时分秒的（$if_h=1表示带时分秒）
	 * @param  $type 返回的结果是否需要显示时分秒【$type=0不显示】
	 */
	public function getDateCut($date,$day,$if_h=1,$type=0){
		$stime=strtotime($date);
		$time=strtotime($day." days",$stime);
		if($if_h==1){
			$etime=date("Y-m-d H:i:s",$time);
		}else{
			$etime=date("Y-m-d",$time);
		}
		if($if_h==1 && $type==0){
			$arr=explode(" ",$etime);
			$etime=$arr[0];
		}
		return $etime;
	}
	
	
	/**
	 * +----------------------------------------------------------
	 * JS:kindeditor插件提交的文本中的远程图片转存到本地
	 * 存储目录:/data/userdata/年/月/日/
	 * +----------------------------------------------------------
	 * @access protected
	 * +----------------------------------------------------------
	 * @param string $data
	 * 文本
	 * +----------------------------------------------------------
	 * @return void 返回过滤之后的文本
	 * +----------------------------------------------------------
	 * @throws ThinkExecption
	 * +----------------------------------------------------------
	 */
	public function remoteimg($data, $create_time = null) {
		if (! empty ( $create_time )) {
			$time_array = array ();
			$time_array = explode ( '-', $create_time );
			// 文件保存目录路径
			$imgPath = USER_DATA_DIR_ROOT . DIRECTORY_SEPARATOR . $time_array [0] . DIRECTORY_SEPARATOR . $time_array [1] . DIRECTORY_SEPARATOR . $time_array [2] . DIRECTORY_SEPARATOR;
			$imgUrl_one = "/data/userdata/" . $time_array [0] . "/" . $time_array [1] . '/' . $time_array [2] . '/';
		} else {
			$imgPath = USER_DATA_DIR_ROOT . DIRECTORY_SEPARATOR . date ( "Y" ) . DIRECTORY_SEPARATOR . date ( "m" ) . DIRECTORY_SEPARATOR . date ( "d" ) . DIRECTORY_SEPARATOR;
			$imgUrl_one = "/data/userdata/" . date ( "Y" ) . "/" . date ( "m" ) . '/' . date ( "d" ) . '/';
		}
		import ( "ORG.Util.Image" );
		// 日期名
		$milliSecond = time ();
		$img_array = array ();
// 		$data = html_entity_decode ( $data );
		$pattern = '/<[img|IMG].*?src=[\'|\"](http.*?[gif|jpg|jpeg|bmp|png])[\'|\"].*?[\/]?>/';
		preg_match_all ( $pattern, $data, $img_array );
		$img_array = array_unique ( $img_array [1] );
		$arr = array ();
		foreach ( $img_array as $key => $value ) {
			//获取图片的等比例缩放的宽高
			$imginfo=getimagesize($value);
			$max_width = $imginfo [0];
			$max_height = $imginfo [1];
			if ($max_width > 500) {
				$thumb_width=500;
				$thumb_height = $max_height * (500 / $max_width);
			} else {
				$thumb_width=500;
				$thumb_height = $max_height;
			}
				
			$get_file = @file_get_contents ( $value );
			$arr = explode ( '.', $value );
			$count = count ( $arr );
			$fileurl = $imgPath . $milliSecond . $key . '.' . $arr [$count - 1];
			$imgUrl = $imgUrl_one . $milliSecond . $key . '.' . $arr [$count - 1];
			if ($get_file) {
				dir_create ( $imgPath );
				$fp = @fopen ( $fileurl, 'w' );
				@fwrite ( $fp, $get_file );
				@fclose ( $fp );
				Image::thumb ( $fileurl, $fileurl, "", $thumb_width, $thumb_height );
			}
			$data = str_replace ( $value, $imgUrl, $data );
		}
		return $data;
	}
	
	
	/**
	 * 处理图文混排的数据【最后返回的内容严格按照原文的样式先图后文的顺序】
	 * @author penglele
	 */
	public function getNewImgContent($data){
		$data=$this->remoteimg($data);
		//正则匹配出所有的img标签
		$pattern = '/[\<][img|IMG].*?src=[\'|\"](.*?[gif|jpg|jpeg|bmp|png])[\'|\"].*?[\/]?>/';
		preg_match_all ( $pattern, $data, $preg_img );
		$img_array = $preg_img [1] ;
		$img_array_all=  $preg_img [0] ;
		$info=array();//需要内容形式为一图一文
		$return_data="";
		$return_img="";
		$img_list=array();
		if($img_array){
			$img="";
			foreach($img_array as $key=>$val){
				$arr=explode($img_array_all[$key],$data,2);//分离图片和内容
				$info[$key]['content']=$this->clean_tags($arr[0]);
				if($key==0){
					$info[$key]['img']=$img;
					$img=$val;
				}else{
					if($img){
						$info[$key]['img']=$img;
						$img=$val;
					}else{
						$info[$key]['img']=$val;
					}
				}

				//返回图文混排中的一张图片
				if(!$return_img && $info[$key]['img']){
					$return_img=$info[$key]['img'];
				}
				
				//内容中所有的图片列表
				if($info[$key]['img'] && !in_array($info[$key]['img'],$img_list)){
					$img_list[]=$info[$key]['img'];
				}	
					
				$return_data=$arr[0];
				$data=$arr[1];
				
				if($key==0 && !$info[$key]['content'] && !$info[$key]['img']){
					unset($info[0]);
				}
			}
			$data=$this->clean_tags($data);
			if($img){
				if(!$return_img){
					$return_img=$img;
				}
				$other['img']=$img;
				$other['content']=$data;
				$info[]=$other;
				$return_data=$this->clean_tags($return_data);
				$return_data=$return_data.$data;
			}
			if(!$info){
				$get_info['img']="";
				$get_info['content']=$data;
				$info[]=$get_info;
			}
			$return['clean_content']=$this->clean_tags($return_data);
			$return['clean_img']=$return_img;
			$return['content']=$info;
			$return['imglist']=$img_list;
		}else{
			$data=$this->clean_tags($data);
			$info['img']="";
			$info['content']=$data;
			$return['content'][]=$info;
			$return['clean_content']=$data;
			$return['clean_img']="";
		}
		return $return;
	}
	
	/**
	 * 过滤标签
	 * @author penglele
	 */
	public function clean_tags($data,$tags=array()){
		$data=$this->clean_html_data($data);
		if(empty($tags)){
			$tags=array("\r","\n");
		}
		$data=strip_tags($data);
		$data=str_replace($tags, "", $data);
		$data=trim($data);
		return $data;
	}
	
	/**
	 * 过滤script标签
	 * @param  $data
	 */
	function clean_html_data($data){
		$par='/<(script).*?>.*?<\/(script)>/si';
		$data=preg_replace($par,"",$data);
		$kong_par="/(&nbsp;)/";
		$data=preg_replace($kong_par,"",$data);
		$data=trim($data);
		return $data;
	}
	

	/**
	 * 获取时间字符串中的每一项
	 * @param string $time 时间【date("Y-m-d H:i:s")】
	 * @param int $num
	 * @return array $list [年月日时分秒/确定的某一值]
	 * @author penglele
	 */
	public function getPerDate($time,$num=0,$type){
		if(empty($type)){
			$type=array("-",":");
		}
		$arr=explode(" ",$time);
		$list=array();
		foreach($arr as $ikey=>$ival){
			$arr_per=array();
			$arr_per=explode($type[$ikey],$ival);
			foreach($arr_per as $eval){
				$list[]=$eval;
			}
		}
		return (int)$list[$num];
	}
	
	
	
}