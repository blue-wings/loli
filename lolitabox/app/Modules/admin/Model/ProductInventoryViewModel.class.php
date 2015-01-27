<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 1/27/15
 * Time: 11:30 AM
 */

class ProductInventoryViewModel extends ViewModel {

    protected $viewFields = array (
        'Products' => array (
            'pid','inventory_item_id','user_type','start_time','end_time','sale_out_time','inventory','price','member_price','inventoryreduced','pre_share_sort_num','sort_num',
            '_table' => 'products',
            '_type' => 'LEFT'
        ),
        'Inventory_item' => array (
            'name','inventory_real',
            '_table' => 'inventory_item',
            '_on' => 'Products.inventory_item_id=Inventory_item.id',
            '_type' => 'LEFT'
        )
    );
}