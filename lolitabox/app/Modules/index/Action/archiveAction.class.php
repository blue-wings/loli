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
//    public function initIndex(){
//        $indexModel = D("ArchiveIndex");
//        $indexModel->initIndex();
//    }
    public function mine(){
        $userinfo=$this->userinfo;
        $properties = D("Archive")->getIndexProperty($this->getUserid());

        $this->assign("userinfo",$userinfo);
        $this->assign("properties",$properties);

        $this->display();
    }
    public function edit(){
        $userinfo=$this->userinfo;
        $properties = D("Archive")->getIndexProperty($this->getUserid());

        $this->assign("userinfo",$userinfo);
        $this->assign("properties",$properties);

        $this->display();
    }
    public function update(){
        $user_profile_model=M("UserProfile");
        if($userData = $user_profile_model->create()) {
            $result =  $user_profile_model->save($userData);
            if($result) {
                $this->success('操作成功！');
            }else{
                $this->error('写入错误！');
            }
        }else{
            $this->error($user_profile_model->getError());
        }
    }

    public function updateUserArchive(){
        $propertyId = $_POST["property_id"];
        $valueIds = explode(",",$_POST["value_ids"]);

        $names = D('Archive')->updateUserArchive($this->userid,$propertyId,$valueIds);
        if($names===false){
            $this->ajaxReturn(1,'保存失败!',0);
        }else{
            $this->ajaxReturn($names,'保存成功!',1);
        }
    }

}