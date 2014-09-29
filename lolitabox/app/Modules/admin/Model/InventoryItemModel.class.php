<?php

class InventoryItemModel extends model{
	
    /**
	 * 入库单审核后，更新对应库存的各个指标
	 */
	public function IncInventoryInLock($inventoryItemId, $quantity){
		try{
			M()->startTrans();
            D("DBLock")->getSingleInventoryItemLock($inventoryItemId);
			$param["id"]=$inventoryItemId;
			$this->where($param)->setInc("inventory_in", $quantity);
			$sql= "UPDATE `inventory_item` SET inventory_real=inventory_in-inventory_abnormal_out-product_shelved_inventory_out,inventory_estimated=inventory_in-inventory_abnormal_out-product_shelved_inventory_in WHERE id=".$inventoryItemId;
			$this->db->execute ( $sql );
			M()->commit();
		}catch (Exception $e){
			M()->rollback();
			throw new Exception("入库失败");
		}
	}
	
	/**
	 * 从库存上架到前台产品
	 * @param unknown_type $pid 上架到前台的产品id
	 * @param unknown_type $inventoryItemId  库存id
	 * @param unknown_type $quantity 本次上架的增量库存
	 * @throws Exception
	 */
	public function shelveProductInventory($pid, $inventoryItemId, $quantity){
		$productMod = D("Product");
		try{
			$inventoryItem = D("DBLock")->getSingleInventoryItemLock($inventoryItemId);
			if($inventoryItem["inventory_estimated"] < $quantity){
				throw new Exception("库存不足");
			}
			$params["id"]=$inventoryItemId;
			$this->where($params)->setInc("product_shelved_inventory_in", $quantity);
			$sql= "UPDATE `inventory_item` SET inventory_real=inventory_in-inventory_abnormal_out-product_shelved_inventory_out,inventory_estimated=inventory_in-inventory_abnormal_out-product_shelved_inventory_in WHERE id=".$inventoryItemId;
			$this->db->execute ( $sql );
			$productParams["pid"]=$pid;
			$productMod->where($productParams)->setInc("inventory", $quantity);
			M()->commit();
		}catch (Exception $e){
			M()->rollback();
			throw new Exception("库存上架到商品失败");
		}
	}

    /**
     * 人工或虚拟出库单审核后，更新对应库存的各个指标
     */
    public function updateAbnormalInventoryOutLock($inventoryItemId, $quantity){
        try{
            M()->startTrans();
            D("DBLock")->getSingleInventoryItemLock($inventoryItemId);
            $param["id"]=$inventoryItemId;
            $this->where($param)->setInc("inventory_abnormal_out", $quantity);
            $sql= "UPDATE `inventory_item` SET inventory_real=inventory_in-inventory_abnormal_out-product_shelved_inventory_out,inventory_estimated=inventory_in-inventory_abnormal_out-product_shelved_inventory_in WHERE id=".$inventoryItemId;
            $this->db->execute ( $sql );
            M()->commit();
        }catch (Exception $e){
            M()->rollback();
            throw new Exception("出库失败");
        }
    }
	
}
