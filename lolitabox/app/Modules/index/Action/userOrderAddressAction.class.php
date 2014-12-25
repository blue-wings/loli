<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 9/26/14
 * Time: 4:07 PM
 */
class userOrderAddressAction extends commonAction {

    public function index(){
        $this->assign("addressSelect","select   ");
        $this->display();
    }

    public function myList(){
        $userOrderAddresses = M("UserOrderAddress")->where(array("userid"=>$this->userid))->order("if_active DESC, id DESC")->select();
        if($userOrderAddresses){
            foreach ($userOrderAddresses as $key=>$userOrderAddress){
                $userOrderAddress["provinceName"]=M("area")->field("title")->getByAreaId($userOrderAddress["province_area_id"]);
                $userOrderAddress["provinceName"]=$userOrderAddress["provinceName"]["title"];
                $userOrderAddress["cityName"]=M("area")->field("title")->getByAreaId($userOrderAddress["city_area_id"]);
                $userOrderAddress["cityName"]=$userOrderAddress["cityName"]["title"];
                $userOrderAddress["districtName"]=M("area")->field("title")->getByAreaId($userOrderAddress["district_area_id"]);
                $userOrderAddress["districtName"]=$userOrderAddress["districtName"]["title"];
                $userOrderAddresses[$key]=$userOrderAddress;
            }
        }
        $this->assign("userOrderAddresses", $userOrderAddresses);
        $this->display();
    }

    public function toAddAddress(){
        $orderId = $_GET["orderId"];
        $this->assign("orderId", $orderId);
        $this->assign("maxAddressNumPerUser", C("MAX_ADDRESS_NUM_PER_USER"));
        $this->display();
    }

    public function saveAddress(){
        $orderId = $_POST["orderId"];
        $count = M("UserOrderAddress")->where(array("userid"=>$this->userid))->count();
        if($count >= C("MAX_ADDRESS_NUM_PER_USER")){
            $this->ajaxReturn(array("status"=>"n","info"=>"超出限定数量，添加失败"), "JSON");
        }
        if(!isset($_POST["province_area_id"]) || !isset($_POST["city_area_id"]) || !isset($_POST["district_area_id"])){
            $this->ajaxReturn(array("status"=>"n","info"=>"请选择省市区，添加失败"), "JSON");
        }
        if( !isset($_POST["linkman"]) || !isset($_POST["telphone"]) || !isset($_POST["address"]) || !isset($_POST["postcode"])){
            $this->ajaxReturn(array("status"=>"n","info"=>"请填写完整信息，添加失败"), "JSON");
        }
        $address["userid"]=$this->userid;
        $address["linkman"]=$_POST["linkman"];
        $address["telphone"]=$_POST["telphone"];
        $address["province_area_id"]=$_POST["province_area_id"];
        $address["city_area_id"]=$_POST["city_area_id"];
        $address["district_area_id"]=$_POST["district_area_id"];
        $address["address"]=$_POST["address"];
        $address["postcode"]=$_POST["postcode"];
        $address["if_active"] = $_POST["if_active"]? C("USER_ORDER_ADDRESS_ACTIVE"):C("USER_ORDER_ADDRESS_NOT_ACTIVE");
        $address["addtime"]=date("Y-m-d H:i:s",time());
        if($address["if_active"] && $address["if_active"] == C("USER_ORDER_ADDRESS_ACTIVE")){
            M("UserOrderAddress")->where(array("userid"=>$this->userid))->save(array("if_active"=>C("USER_ORDER_ADDRESS_NOT_ACTIVE")));
        }
        M("UserOrderAddress")->add($address);
        $this->ajaxReturn(array("status"=>"y","info"=>"添加成功"), "JSON");
    }

    public function del(){
        $id = $_GET["id"];
        M("UserOrderAddress")->where(array("id"=>$id))->delete();
        $this->ajaxReturn(array("result"=>true), "JSON");
    }

    public function setDefault(){
        $id = $_GET["id"];
        M("UserOrderAddress")->where(array("userid"=>$this->userid))->save(array("if_active"=>C("USER_ORDER_ADDRESS_NOT_ACTIVE")));
        M("UserOrderAddress")->where(array("userid"=>$this->userid, "id"=>$id))->save(array("if_active"=>C("USER_ORDER_ADDRESS_ACTIVE")));
        $this->ajaxReturn(array("result"=>true), "JSON");
    }

}