<?php
class UserSelfPackageOrderAddrModel extends ViewModel {

    protected $viewFields = array (
        'UserOrder' => array (
            'ordernmb',
            'userid',
            'cost',
            'pay_bank',
            'state',
            'trade_no',
            'addtime',
            'paytime',
            'address_id',
            '_table' => 'user_self_package_order',
            '_type' => 'LEFT'
        ),
        'Users' => array (
            'nickname' => 'username',
            'usermail',
            'order_num',
            '_table' => 'users',
            '_on' => 'UserOrder.userid=Users.userid',
            '_type' => 'LEFT'
        ),
        'UserOrderAddress' => array (
            'linkman',
            'telphone',
            'province_area_id',
            'city_area_id',
            'district_area_id',
            'address',
            'postcode',
            '_table' => 'user_order_address',
            '_on' => 'UserOrderAddress.id=UserOrder.address_id',
            '_type' => 'LEFT'
        ),
        'UserOrderSend' => array (
            'proxyorderid',
            'inventory_out_Id',
            '_table' => 'user_order_send',
            '_on' => 'UserOrderSend.orderid=UserOrder.ordernmb',
            '_type' => 'LEFT'
        ),
        'UserOrderSendProductDetail' => array(
            'productid',
            'inventory_item_id',
            '_table' => 'user_order_send_productdetail',
            '_on' => 'UserOrderSendProductDetail.self_package_order_id=UserOrder.ordernmb',
            '_type' => 'LEFT'
        )

    );
}
?>