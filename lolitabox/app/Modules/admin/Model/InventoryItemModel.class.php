<?php

class InventoryItemModel extends Model{

    protected $_auto=array(
        array('for_skin','getSkinList',3,'callback'),
        array('for_people','getPeopleList',3,'callback'),
        array('for_hair','getHairList',3,'callback'),
    );

    function getSkinList(){
        $str=implode(",", $_POST["for_skin"]);
        return $str;
    }

    function getPeopleList(){
        $str=implode(",", $_POST["for_people"]);
        return $str;
    }

    function getHairList(){
        $str=implode(",", $_POST["for_hair"]);
        return $str;
    }

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
	 * @param unknown_type $inventoryItemId  库存id
	 * @param unknown_type $quantity 本次上架的增量库存
	 * @throws Exception
	 */
    public function shelveProductInventory($inventoryItemId, $quantity){
        $inventoryItem = $this->getById($inventoryItemId);
        if ($inventoryItem["inventory_estimated"] < $quantity) {
            throw new Exception("库存不足");
        }
        $params["id"] = $inventoryItemId;
        $this->where($params)->setInc("product_shelved_inventory_in", $quantity);
        $sql = "UPDATE `inventory_item` SET inventory_real=inventory_in-inventory_abnormal_out-product_shelved_inventory_out,inventory_estimated=inventory_in-inventory_abnormal_out-product_shelved_inventory_in WHERE id=" . $inventoryItemId;
        $this->db->execute($sql);
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
