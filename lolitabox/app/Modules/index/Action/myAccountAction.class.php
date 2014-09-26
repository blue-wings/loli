<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 9/26/14
 * Time: 4:54 PM
 */
class myAccountAction extends commonAction {

    public function index(){
        $this->redirect("userOrderAddress/myAddresses");
    }
}