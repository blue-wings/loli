<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 8/19/14
 * Time: 6:16 PM
 */
class archiveAction extends commonAction{

    function mineArchive(){
        $properties = M("ArchiveProperty")->select();
        $propertyList = array();
        foreach($properties as $key => $property){
            $propertyValues = M("archiveValue")->where(array("property_id"=>$property["id"]))->select();
            if(!$propertyValues){
                continue;
            }
            foreach($propertyValues as $index=>$propertyValue){
                $userValues = M("archiveUser")->where(array( "userid"=>$this->userid, "property_id"=>$property["id"], "value_id"=>$propertyValue["id"]))->select();
                if($userValues){
                    $propertyValue["check"]=1;
                }
                $propertyValues[$index]=$propertyValue;
            }
            $property["values"]=$propertyValues;
            array_push($propertyList, $property);
        }
        $this->assign("propertyList", $propertyList);
        $this->display();
    }

    function updateArchive(){
        $properties = M("ArchiveProperty")->select();
        if($properties){
            M("ArchiveUser")->where(array("userid"=>$this->userid))->delete();
            foreach($properties as $property){
                $propertyValueIds = $_POST["property-".$property["id"]];
                if(!$propertyValueIds){
                    continue;
                }
                foreach($propertyValueIds as $propertyValueId){
                    $data["userid"]=$this->userid;
                    $data["property_id"]=$property["id"];
                    $data["value_id"]=$propertyValueId;
                    M("ArchiveUser")->add($data);
                }
            }
        }
        $this->redirect("Archive/mineArchive");
//        $this->ajaxReturn(array("result"=>true));
    }

}