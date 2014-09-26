<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 9/26/14
 * Time: 4:07 PM
 */
class userOrderAddressAction extends commonAction {

    public function myAddresses(){
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

}