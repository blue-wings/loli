<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 9/26/14
 * Time: 4:07 PM
 */
class userOrderAddressAction extends commonAction {

    public function myAddresses(){
        $userOrderAddresses = M("UserOrderAddress")->where(array("userid"=>$this->userid))->order("id DESC")->select();
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
        $orderId = $_POST["orderId"];
        $this->assign("orderId", $orderId);
        $this->display();
    }

    public function saveAddress(){
        $orderId = $_POST["orderId"];

        if($orderId){
            $this->redirect("userOrder/getOrder2Complete", array("orderId"=>$orderId));
        }
        $this->redirect("userOrderAddress/myAddresses");
    }

    public function getProvincesJson(){

    }

    public function getCitiesJson(){

    }

    public function getDistrictsJson(){

    }

}