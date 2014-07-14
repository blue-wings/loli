<?php
class UserOrderAction extends commonAction {
	
    public function generateOrder() {
        $productIds = $_POST("productIds");
        $userId = $this->userid;
        D("ShoppingCart")->invalidUserShoppingCartProducts($userId);
        $orderId = D("UserOrder")->addOrder($userId, $productIds, $addressId,$payBank,$ifUseGiftCard,$sendWord, $expressCompanyId);
        return D("UserOrder")->getOrderDetail($orderId);
    }
    
    
    public function completeOrderAnd2Pay(){
    	$orderId = $_POST["orderId"];
        $sendWord = $_POST("send_word");
        import("ORG.Util.String");
        if(mb_strlen($sendWord,"utf8") > 200){
            $sendWord=String::msubstr($sendWord ,0,200,'utf-8',false);
        }
        $expressCompanyId = $_POST("expressCompanyId");
        $ifUseGiftCard = $_POST("ifUseGiftCard");
        $payBank = $_POST("payBank");
         $addressId = $_POST("addressId");
    }
    
}