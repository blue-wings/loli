<?php
class UserOrderAction extends commonAction {

    public function generateOrder() {
        $sendWord = $_POST("send_word");
        import("ORG.Util.String");
        if(mb_strlen($sendWord,"utf8") > 200){
            $sendWord=String::msubstr($sendWord ,0,200,'utf-8',false);
        }
        $productIds = $_POST("productIds");
        $expressCompanyId = $_POST("expressCompanyId");
        $userId = $this->userid;
        $ifUseGiftCard = $_POST("ifUseGiftCard");
        $payBank = $_POST("payBank");
        $addressId = $_POST("addressId");
        $orderId = D("UserOrder")->addOrder($userId, $productIds, $addressId,$payBank,$ifUseGiftCard,$sendWord, $expressCompanyId);
        return D("UserOrder")->getOrderDetail($orderId);
    }
}