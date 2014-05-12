<?php

/**
 * 运营数据管理，提供订单统计等方法
 * @author litingting
 *
 */
class DataStatAction extends CommonAction{


    /**
     * 订单统计
     * @author litingting
     */
    public function orderStat(){

        $_REQUEST['start'] = empty($_REQUEST['start'])? date("Y-m")."-01" : $_REQUEST['start'];
        $_REQUEST['end'] = empty($_REQUEST['end'])? date("Y-m-d") : $_REQUEST['end'];
        $stat_mod = M("StatUserOrder");
        $user_mod = M("Users");
        $order_mod = M("UserOrder");    		

        //折线图
        if($_REQUEST['load']=='img'){
            $this->getImage($_REQUEST['start'],$_REQUEST['end']);
			die;
        }
        if($_REQUEST['load']=='all'){
            $this->getImage(null,null);
			die;
        }

        if($this->_post('query')){
            $this->queryList();
        }

        $statlist = $stat_mod ->where("statdate >= '".$_REQUEST['start']."' and statdate <='".$_REQUEST['end']."'")->select();
        $total=count($statlist);
        $list = array (
            'registernum' => 0,
            'ordernum' => 0,
            'sales' => 0,
            'discount' => 0,
            'totalvalue_all' => 0,
        	'totalvalue'=>0,
            'totalprice' => 0 
        );
        foreach($statlist as $key =>$val){
            $list['registernum'] +=$val['registernum'];
            $list['ordernum'] +=$val['ordernum'];
            $list['ordernum_exchange'] +=$val['ordernum_exchange'];
            $list['ordernum_postage'] +=$val['ordernum_postage'];
            $list['sales'] +=$val['sales'];
            $list['sales_bianxian'] +=$val['sales']-25;
            $list['sales_exchange'] +=$val['sales_exchange'];
            $list['sales_postage'] +=$val['sales_postage'];
            $list['discount'] +=$val['discount'];
            $list['totalvalue_all'] +=$val['totalvalue_all'];
            $list['totalvalue'] +=$val['totalvalue'];
            $list['totalprice'] +=$val['totalprice'];
        }

        //注册用户下单数
        $reg_order_num=0;
        $reg_order_num = $user_mod ->field("userid")->where("addtime >='".$_REQUEST['start']." 00:00:00' AND  addtime <= '".$_REQUEST['end']." 23:59:59' AND userid IN(SELECT DISTINCT userid FROM user_order WHERE addtime >='".$_REQUEST['start']." 00:00:00' AND  addtime <= '".$_REQUEST['end']." 23:59:59' AND state =1)")->count();


        if($this->_get('order')){
            if($this->_get('by') == 1){
                $order = $this->_get('order').' DESC'; 
            }else{
                $order = $this->_get('order').' ASC'; 
            }
        }else{
            $order = "statdate DESC";
        }  

        //分页
        import("ORG.Util.Page");
        $p=new Page($total,30);
        $statlist = $stat_mod ->where("statdate >= '".$_REQUEST['start']."' and statdate <='".$_REQUEST['end']."'")->limit($p->firstRow,$p->listRows)->order($order)->select();
        $page=$p->show();

        $list['reg_order_num']=$reg_order_num;
        $this->assign("list",$list);
        $this->assign("total",$total);
        $this->assign("statlist",$statlist);
        $this->assign("page",$page);
        $this->display();

    }

    //订单数据统计
    //统计该时间段注册人数以及订单数
    //zhao
    private function queryList(){

    	
        if($this->_post('query') == 'registernum'){

            $userlist =  M("Users")->where(array("addtime"=>array('between',array($this->_post('time').' 00:00:00',$this->_post('time').'  23:59:59'))))->field('userid,nickname,usermail,addtime')->select();

            $this->assign('rcount',count($userlist));
            $this->assign('ulist',$userlist);
            $this->display('queryDataUserList');

        }else{

        	switch ($this->_post('query')) {
        		case "ordernum":
        			$where =  array(
	        			'addtime'=>array('LIKE',$this->_post('time').'%'),
	        			'type'=>array('NOT IN','(16,18,19,21)'),
	        			'state'=>1,
	        			'ifavalid'=>1
        			);
        			break;
        			case "ordernum_exchange":
        				$where =  array(
        				'addtime'=>array('LIKE',$this->_post('time').'%'),
        				'type'=>"18",
        				'state'=>1,
        				'ifavalid'=>1
        				);
        			break;
        			case "ordernum_postage":
        				$where =  array(
        				'addtime'=>array('LIKE',$this->_post('time').'%'),
        				'type'=>"19",
        				'state'=>1,
        				'ifavalid'=>1
        				);
        				break;
        	}
        	

            $list = M("UserOrder")->where($where)->field('ordernmb,userid,boxid,addtime,paytime,boxprice,discount')->select();

            foreach($list as $key => $value){
                $list[$key]['nickname'] = M('Users')->where(array('userid'=>$value['userid']))->getField('nickname');
                $list[$key]['boxname']  = M("Box")->where(array('boxid'=>$value['boxid']))->getField("name");

            }
            $this->assign('rcount',count($list));
            $this->assign('orderlist',$list);
            $this->display('queryOrderMsg');
        }

        exit();
    }

    /**
     * 获取折线图
     * @author litingting
     */
    private function getImage($start=null,$end=null){
        $stat_mod = M("StatUserOrder");
        import("@.ORG.Util.ImageReport");
        $type=$_REQUEST['type'];
        $temparray=array();
        $titlearray=array(
            "ordernum" => "订单数折线图",
            "registernum" => "注册数折线图",
            "sales"  => "销售额折线图",
            "discount" => "折扣额折线图",
            "totalvalue" => "售出产品总额",
            "totalprice" => "应收总额折线图",
        );

        if($start==null){        //全局折线图（按月汇总)
            $list=$stat_mod->field("DATE_FORMAT( statdate,'%Y-%m') as statdate,sum(ordernum) as ordernum,sum(registernum) as registernum,sum(sales) as sales,sum(discount) as discount,sum(totalvalue) as totalvalue,sum(totalprice) as totalprice")->group("DATE_FORMAT( statdate,'%Y-%m' ) ")->select();
            $a = 0;
            foreach ($list as $key =>$val){
                $temparray[]=$val[$type];
                $temps = explode("-",$val['statdate']);
                $temp = $temps [0];
                if($temp !=$a){
                    $a = $temp;
                    $xarray[] = $val['statdate'];
                }else{
                    $xarray[] =(int) $temps[1];
                }
            }

            $report=new ImageReport;
            $width=40*count($temparray)+60;
            $report->setImage($width,180,255,255,255,1);
            $temparray=implode(",",$temparray);
            $report->setItem(',',$temparray,3,40);//参数(分隔数值的指定符号,数值变量,样式1为竖柱图2为横柱图3为折线图,距离)
            $report->setFont(2);//字体大小1-10
            $report->PrintReport($titlearray[$type],$xarray);

        }else{

            $list = $stat_mod  ->where("statdate >= '".$start."' and statdate <='".$end."'")->select();
            $a=0;
            foreach ($list as $key =>$val){
                $temparray[]=$val[$type];
                $temps = explode("-",$val['statdate']);
                if($temps[1] !=$a){
                    $a = $temps[1];
                    $xarray[] = (int)$temps[1]."-".(int)$temps[2];
                }else{
                    $xarray[] =(int) $temps[2];
                }
            }
            $report=new ImageReport;
            $width=25*count($temparray)+30+20;
            $report->setImage($width,180,255,255,255,1);
            $temparray=implode(",",$temparray);
            $report->setItem(',',$temparray,3,25);//参数(分隔数值的指定符号,数值变量,样式1为竖柱图2为横柱图3为折线图,距离)
            $report->setFont(2);//字体大小1-10
            $report->PrintReport($titlearray[$type],$xarray);
        }

    }

}
