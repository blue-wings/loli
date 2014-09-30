<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 9/29/14
 * Time: 2:21 PM
 */
class DBLockModel extends Model {

    public function getSingleProductLock($pId){
        $product = D("Products")->lock(true)->getByPid($pId);
        return $product;
    }

    public function getProductsLock($pIds){
        $lockWhere["pid"] = array("in", $pIds);
        $products = D("Products")->where($lockWhere)->lock(true)->select();
        return $products;
    }

    public function getSingleOrderLock($orderId){
        $lockWhere["ordernmb"] = $orderId;
        $order = D("UserOrder")->lock(true)->getByOrdernmb($orderId);
        return $order;
    }

    public function getSingleUserLock($userId){
        $user = M("users")->lock(true)->getByUserid($userId);
        return $user;
    }

    public function getSingleInventoryItemLock($inventoryItemId){
        $inventoryItem = D("InventoryItem")->lock(true)->getById($inventoryItemId);
        return $inventoryItem;
    }

    public function getSingleSelfPackageOrderLock($orderId){
        $order = D("UserSelfPackageOrder")->lock(true)->getById($orderId);
        return $order;
    }

}