<?php

/**
 * 邮费标准类
 * @author work
 *
 */
class PostageStandardModel extends Model {
	
	/**
	 * 按订单计算邮费
	 * @param unknown_type $orderId
	 * @param unknown_type $expressCompanyId
	 * @param unknown_type $areaId
	 */
	public function calculateOrderPostage($orderId, $expressCompanyId, $areaId){
		if(!isset($orderId) || !isset($expressCompanyId) || !isset($areaId)){
			throw_exception("参数不全，无法计算邮费");	
		}
		$userOrderSendProductDetailModel = D("UserOrderSendProductdetail");
		$where["orderid"]=$orderId;
		$productIds = $userOrderSendProductDetailModel->field("productid")->where($where)->select();
		return $this->calculatePostage($productIds, $expressCompanyId, $areaId);
	}
	
	/**
	 * 计算邮费,传入最细的areaid，将根据area的层级关系向上找到第一个配置邮费的记录
	 * @param $userOrderId
	 * @param $expressType 见CONSTANTS中的定义
	 * @param $areaId
	 */
	public function calculatePostage($productIds, $expressCompanyId, $areaId){
		if(!$productIds || count($productIds)==0 || !isset($expressCompanyId) || !isset($areaId)){
			throw_exception("参不数全，无法计算邮费"); 	
		}
		$areaModel = M("Area");
		$areaWhere["area_id"]=$areaId;
		$area = $areaModel->where($areaWhere)->find();
		$postageStandard = null;
		while (true){
			if(!$area){
				break;
			}
			$where["express_company_id"]=$expressCompanyId;
			$where["areaId"]=$area["area_id"];
			$postageStandard = $this->where($where)->find();
			if($postageStandard){
				break;
			}
			$areaWhere["id"]=$area["pid"];
			$area = $areaModel->where($areaWhere)->find();
		}
		if(!$postageStandard){
			return null;
		}
		$productsModel = D("Products");
		$inventoryItemModel = D("InventoryItem");
		$totalWeight=0;
		foreach ($productIds as $productId){
			$whereProduct["pid"]= $productId;
			$product = $productsModel->where($whereProduct)->find();
			if(!$product){
				throw_exception("无效的productId，无法计算邮费"); 	
			}
			if(!$product["inventory_item_id"]){
				throw_exception("产品未关联库存，无法计算邮费"); 
			}
			$whereInventoryItem["id"] = $product["inventory_item_id"];
			$inventoryItem = $inventoryItemModel->where($whereInventoryItem)->find();
			$totalWeight += bcadd($totalWeight, $inventoryItem["weight"]);
		}
		$postage = null;
		if(bccomp($totalWeight, 1000) <= 0){
			$postage = $postageStandard["first_heavy"];
		}else{
			$continuedHeavyUnit = bcdiv($totalWeight, 1000, 0);
			$postage = $postageStandard["first_heavy"] + $postageStandard["continued_heavy"]* (intval($continuedHeavyUnit));	
		}
		return round($postage/100);
	}
	
	
	

}