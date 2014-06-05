<?php

class InventoryItemModel extends model{
	
	/**
	 * 根据库存单品ID统计理论库存数
	 * @param string $ids  库存单品ID列表
	 * @author litingting
	 */
	public function updateInventoryByIds($ids=null){
		 $sql="SELECT productid,count(productid) AS T  FROM `user_order_send_productdetail` WHERE orderid IN (SELECT A.orderid FROM `user_order_send` AS A,user_order AS B WHERE (A.orderid=B.ordernmb) AND (A.productnum>0 AND A.senddate IS NULL AND A.proxysender IS NULL AND A.proxyorderid IS NULL) AND (B.state=1 AND B.ifavalid=1 AND B.inventory_out_status=0)) ";
		 if($ids)
		 	$sql.=" AND productid IN($ids) ";
		 $sql.=" GROUP BY productid ORDER BY NULL";
		 $list = $this->db->query ( $sql );
		 while ( list ( $key, $item ) = each ( $list ) ) {
			if ($item ['productid']) {
				$updatesql = "UPDATE inventory_item SET relation_out_quantity=" . $item ['T'] . " WHERE id=" . $item ['productid'];
				$this->db->execute ( $updatesql );
			}
		}
		
		$sql= "UPDATE `inventory_item` SET inventory_estimated=inventory_in+inventory_out-relation_out_quantity WHERE 1 ";
		if($ids) 
			$sql.=" AND id IN($ids)";
// 		echo $sql;
		$this->db->execute ( $sql );
	}
	
    /**
	 * 统计系统出库入库数及实际库存数和理论库存数
	 * @author litingting
	 */
	public function updateInventoryByInOut(){
		$sql = "UPDATE `inventory_item` SET inventory_in=(SELECT SUM(quantity) FROM inventory_stat WHERE inventory_item.id=itemid AND quantity>0 AND  status=1) ,inventory_out=(SELECT SUM(quantity) FROM inventory_stat WHERE inventory_item.id=itemid AND quantity<0 AND  status=1)";
		$this->db->execute ( $sql );
		$sql= "UPDATE `inventory_item` SET inventory_real=inventory_in+inventory_out,inventory_estimated=inventory_in+inventory_out-relation_out_quantity WHERE 1";
		$this->db->execute ( $sql );
	}
}
