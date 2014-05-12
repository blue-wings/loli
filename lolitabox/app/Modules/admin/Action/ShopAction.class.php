<?php
class ShopAction extends  CommonAction{

	/**
       +----------------------------------------------------------
       * 商家信息操作
       +----------------------------------------------------------  
       * @access public
       +----------------------------------------------------------
       * @param  string aid   	  查询地区信息  			AJAX
       * @param  string sid  	  查询需要编辑的某条记录   AJAX
       * @param  string crshop    添加商家信息
       * @param  string del    	  删除商家的某条信息
       * @param  string search    查找符合条件的的记录
       * @param  string export    导出符合条件的记录     
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.2 22:43
       */		
	public function index(){
		$shop=D('Shop');
		$shop_mod=D('ShopView');
		import("@.ORG.Page");

		$prolist=$this->rplist();
		$pro=$this->area(0);

		if($this->_post('brandname')){
			$picbrand=$this->plist($this->_post('brandname'));
			$this->ajaxReturn($picbrand,1,1);
		}else if($this->_post('aid')){
			$con=$this->area($this->_post('aid'));
			$con2=$this->area($con[0]['area_id']);
			$this->ajaxReturn($con,$con2,1);

		}else if($this->_post('sid')){
			$shopMessage=$shop_mod->where('shop.id='.$this->_post('sid'))->find();
			$this->ajaxReturn($shopMessage,'查询成功!',1);

		}else if($this->_post('crshop') || $this->_post('editid')){
			if($shop->create()){
				if($this->_post('editid')){
					$shop->id=trim($this->_post('editid'));
					if($shop->save()){
						$this->success('商家修改成功!');
					}else{
						$this->error($shop->getError());
					}
				}else{
					if($shop->add()){
						$this->success('商家添加成功!');
					}else{
						$this->error($shop->getError());
					}
				}
			}
			exit();
		}else if($this->_get('del')){
			$result=$shop->delete($this->_get('del'));
			if($result){
				$this->success('删除成功:ID='.$this->_get('del'));
			}else{
				$this->error($shop->getError());
			}
			exit();
		}else{
			if($this->_get('search') || $this->_get('export')){
				if($this->_get('sname')){
					$where['shop.name']=array('LIKE','%'.trim($this->_get('sname')).'%');
				}
				if($this->_get('bid')){
					$where['shop.brandid']=trim($this->_get('bid'));
				}

				if($this->_get('pname')){
					$parray=$this->plist(trim($this->_get('pname')));
					$where['shop.brandid']=$parray[0]['id'];
				}

				if($this->_get('province')){
					$where['shop.province_areaid']=trim($this->_get('province'));
				}

				if($this->_get('city')){
					$where['shop.city_areaid']=trim($this->_get('city'));
				}
				if($this->_get('county')){
					$where['shop.county_areaid']=trim($this->_get('county'));
				}

				$count=$shop_mod->where($where)->count('shop.id');
				$p = new Page($count,15);
				$list=$shop_mod->where($where)->limit($p->firstRow . ',' . $p->listRows)->order("id DESC")->select();
				if($this->_get('export')){
					$list=$shop_mod->where($where)->select();
					$str="商家名称,所属品牌,地址,位置经度,位置纬度,联系人,电话\n";
					foreach ($list as $k => $v){
						$str.=$v['name'].','.$v['pname'].','.$v['title'].$v['stitle'].$v['ttitle'].$v['address'].','.$v['longitude'].','.$v['latitude'].','.$v['linkman'].','.$v['telphone']."\n";
					}
					outputExcel ( iconv ( "UTF-8", "GBK", date ( "Y-m-d" ) . "商家信息" ), $str );
					exit();
				}
			}else{
				$count=$shop_mod->count('shop.id');
				$p = new Page($count,15);
				$list=$shop_mod->limit($p->firstRow . ',' . $p->listRows)->order("id DESC")->select();
			}

			$page = $p->show();
			$this->assign('page',$page);
		}

		$this->assign('slist',$list);
		$this->assign('provice',$pro);
		$this->assign("products",$prolist);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 返回地区信息
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string pid   	  有值则返回符合条件的记录
       * 						  空值则返回一级地区列表
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.2 22:46
       */	
	private function area($pid){
		$area_mod=M("area");
		$where['status']=1;
		$aid=trim($pid);
		if($aid){
			$where['pid']=$aid;
		}else{
			$where['pid']=0;
		}
		$prolist=$area_mod->where($where)->field('area_id,title')->select();

		return $prolist;
	}

	/**
       +----------------------------------------------------------
       * 返回品牌列表
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string pname     有值则返回符合条件的某条记录
       * 						  空值则返回品牌列表
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.2 22:46
       */		
	private function rplist($pname=''){
		$p_mod=M("productsBrand");
		if($pname){
			$where['name']=array('LIKE','%'.$pname.'%');
			$prolist=$p_mod->where($where)->field('id,name')->select();
		}else{
			$prolist=$p_mod->field('id,name')->select();
		}
		return $prolist;
	}

	/**
       +----------------------------------------------------------
       * 改变商家显示状态
       +----------------------------------------------------------
       * @access public	
       +----------------------------------------------------------
       * @param  string type=shop  	  	  修改商家显示状态
       * @param  string type=shopinfo  	  修改商家信息显示状态
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.2 22:49
       */
	public function changeShopSta(){
		switch($this->_post('type')){
			case 'shop':
				$shop_mod=M('shop');
				break;
			case 'shopinfo':
				$shop_mod=M('shopInfo');
				break;
		}
		if($this->_post('sid')){
			$result=$shop_mod->where(array('id'=>$this->_post('sid')))->setField('status',$this->_post('sta'));
			if($result){
				$this->ajaxReturn('','修改成功!',1);
			}else{
				$this->ajaxReturn('','修改失败',0);
			}
		}else{
			$this->ajaxReturn(0,'无参数',0);
		}
	}

	/**
       +----------------------------------------------------------
       * 商家信息列表
       +----------------------------------------------------------
       * @access public	
       +----------------------------------------------------------
       * @param  string infoid  	  	  返回某条商家信息   AJAX
       * @param  string sort  	  		  商家信息排序
       * @param  string delall  	  	  删除商家信息
       * @param  string sub  	  		  添加商家信息
       * @param  string editsub  	  	  修改商家信息
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.2 22:49
       */
	public	function manageShop(){
		$shop_mod=M("shopInfo");
		import("@.ORG.Page");

		if($this->_post('infoid')){
			$returndata=$shop_mod->find($this->_post('infoid'));
			$this->ajaxReturn($returndata,'返回成功!',1);

		}else if($this->_post('sort')){
			$sort=$this->_post('sortid');
			foreach ($sort as $key=>$value){
				$shop_mod->where(array('id'=>$key))->setField('sortid',$value);
			}
			$this->success('修改成功!');
			exit();
		}else if($this->_post('delall')){

			natsort($_POST['id']);
			$where['id']=array('IN',implode(',',$_POST['id']));
			$result=$shop_mod->where($where)->delete();
			if($result){$this->success('删除成功!');}else{$this->error('删除失败');}
			exit();
		}else if($this->_post('sub')){
			if($shop_mod->create()){
				$shop_mod->c_datetime=date("Y-m-d H:i:s",time());
				if($shop_mod->add()){$this->success('添加成功!');}else{$this->error('添加失败');}
			}
			exit();
		}else if($this->_post('editsub')){
			if($shop_mod->create()){
				$shop_mod->c_datetime=date("Y-m-d H:i:s",time());
				if($shop_mod->save()){$this->success('修改成功!');}else{$this->error('修改失败');}
			}
			exit();
		}else{
			$where['shopid']=trim($this->_get('shopid'));
			$count=$shop_mod->where($where)->count('id');
			$p = new Page($count,15);
			$shoplist=$shop_mod->where($where)->limit($p->firstRow . ',' . $p->listRows)->order("sortid DESC,id DESC")->select();

			$page=$p->show();
			$this->assign('name',$this->_get('shopname'));
		}
		$this->assign('page',$page);
		$this->assign('list',$shoplist);
		$this->display();
	}
}
?>