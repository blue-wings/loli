<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 12/25/14
 * Time: 3:47 PM
 */

class giftCardAction extends commonAction {

    public function index(){
        $this->assign("giftCardSelect","select   ");
        $this->display();
    }

    public function toActive(){
        $this->display();
    }

    public function active(){
        $giftCardId=$_POST["cardId"];
        $giftCardPwd=$_POST["cardPwd"];
        $ret = D("GiftCard")->activateGiftCard($giftCardId, $giftCardPwd, $this->userid);
        if($ret == 1){
            $this->ajaxReturn(array("status"=>"y","info"=>"激活成功!"), "JSON");
        }else{
            $this->ajaxReturn(array("status"=>"n","info"=>"激活失败!请联系管理员"), "JSON");
        }
    }

}