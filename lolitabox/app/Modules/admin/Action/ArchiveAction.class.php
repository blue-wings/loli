<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 9/18/14
 * Time: 1:48 PM
 */
class ArchiveAction extends CommonAction {
    function productProperties(){
        $pid = $_GET["pid"];
        $properties = M("ArchiveProperty")->select();
        $propertyList = array();
        foreach($properties as $key => $property){
            $propertyValues = M("archiveValue")->where(array("property_id"=>$property["id"]))->select();
            if(!$propertyValues){
                continue;
            }
            foreach($propertyValues as $index=>$propertyValue){
                $userValues = M("archiveProduct")->where(array( "pid"=>$pid, "property_id"=>$property["id"], "value_id"=>$propertyValue["id"]))->select();
                if($userValues){
                    $propertyValue["check"]=1;
                }
                $propertyValues[$index]=$propertyValue;
            }
            $property["values"]=$propertyValues;
            array_push($propertyList, $property);
        }
        $this->assign("pid", $pid);
        $this->assign("propertyList", $propertyList);
        $this->display();
    }

    function deliverProduct(){
        $pid = $_POST["pid"];
        $properties = M("ArchiveProperty")->select();
        if($properties){
            M("ArchiveProduct")->where(array("pid"=>$pid))->delete();
            foreach($properties as $property){
                $propertyValueIds = $_POST["property-".$property["id"]];
                if(!$propertyValueIds){
                    continue;
                }
                foreach($propertyValueIds as $propertyValueId){
                    $data["pid"]=$pid;
                    $data["property_id"]=$property["id"];
                    $data["value_id"]=$propertyValueId;
                    M("ArchiveProduct")->add($data);
                }
            }
        }
        D("ArchiveIndex")->createProductIndex($pid);
        $this->redirect("Archive/productProperties", array("pid"=>$pid));
    }
}