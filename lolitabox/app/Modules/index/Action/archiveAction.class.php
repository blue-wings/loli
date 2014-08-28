<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 8/19/14
 * Time: 6:16 PM
 */
class archiveAction extends commonAction{

    public $elasticSearchClient;

    function _initialize() {
        require 'vendor/autoload.php';
        $params = array();
        $params['hosts'] = array (
            '127.0.0.1:9200'
        );
        $this->elasticSearchClient = new Elasticsearch\Client($params);
        parent::_initialize();
    }

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
        $this->redirect("archive/mineArchive");
//        $this->ajaxReturn(array("result"=>true));
    }

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
        $this->createProductIndex1($pid);
        $this->redirect("archive/productProperties", array("pid"=>$pid));
    }

    private function createProductIndex1($pid){
        $product = D("Products")->getByPid($pid);
        $index = "product";
        $type="productArchive";
        $searchParams['index'] = $index;
        $searchParams['type']  = $type;
        $searchParams['body']['query']['match']['pid'] = $pid;
        $this->elasticSearchClient->deleteByQuery($searchParams);

        $archiveProducts = M("ArchiveProduct")->where(array("pid"=>$pid))->select();
        $propertyArray = array();
        foreach($archiveProducts as $archiveProduct){
            if(!$propertyArray["property".$archiveProduct["property_id"]]){
                $propertyArray["property".$archiveProduct["property_id"]] = array();
            }
            $propertyValueArray = $propertyArray["property".$archiveProduct["property_id"]];
            array_push($propertyValueArray, $archiveProduct);
            $propertyArray["property".$archiveProduct["property_id"]] = $propertyValueArray;
        }
        $userTypes = split(",", $product["user_type"]);
        $propertyArray["user_type"]=array();
        foreach($userTypes as $userType){
            $value["property_id"]="user_type";
            $value["value_id"]=$userType;
            array_push($propertyArray["user_type"], $value);
        }
        $docArray = array(
            "pid" => $product["pid"],
            "pname" => $product["pname"],
            "firstcid" => $product["firstcid"],
            "secondcid" => $product["secondcid"],
            "status" => $product["status"],
            "start_time" => $product["start_time"],
            "end_time" => $product["end_time"],
            "inventory_item_id" => $product["inventory_item_id"],
            "price" => $product["price"],
            "member_price" => $product["member_price"],
            "pimg" => $product["pimg"]
        );
        foreach($propertyArray as $propertyId => $propertyValueArray){
            $propertyParams = array();
            foreach($propertyValueArray  as $propertyValue){
                $valueId = $propertyValue["value_id"];
                $propertyParams[$propertyId."-".$valueId]=1;
            }
            $docArray = array_merge($docArray, $propertyParams);
        }
        $doc = array(
            "index" => $index,
            "type" => $type,
            "id" => $product["pid"],
            "body" =>array(
                "doc" =>$docArray
            )
        );
        $this->elasticSearchClient->create($doc);
    }

    private function createProductIndex($pid){
        $product = D("Products")->getByPid($pid);
        $index = "product";
        $type="productArchive";
        $searchParams['index'] = $index;
        $searchParams['type']  = $type;
        $searchParams['body']['query']['match']['pid'] = $pid;
        $this->elasticSearchClient->deleteByQuery($searchParams);

        $archiveProducts = M("ArchiveProduct")->where(array("pid"=>$pid))->select();
        $propertyArray = array();
        foreach($archiveProducts as $archiveProduct){
            if(!$propertyArray[$archiveProduct["property_id"]]){
                $propertyArray[$archiveProduct["property_id"]] = array();
            }
            $propertyValueArray = $propertyArray[$archiveProduct["property_id"]];
            array_push($propertyValueArray, $archiveProduct);
            $propertyArray[$archiveProduct["property_id"]] = $propertyValueArray;
        }
        $userTypes = split(",", $product["user_type"]);
        $propertyArray["userType"]=array();
        foreach($userTypes as $userType){
            $value["property_id"]="userType";
            $value["value_id"]="userType".$userType;
            array_push($propertyArray["userType"], $value);
        }
        $indexCount = 1;
        $propertyValueRepeatNumMap=array();
        foreach($propertyArray as $propertyValueArray){
            $indexCount *= count($propertyValueArray);
            foreach($propertyValueRepeatNumMap as $key => $propertyValueRepeatNum){
                $propertyValueRepeatNum *=count($propertyValueArray);
                $propertyValueRepeatNumMap[$key]=$propertyValueRepeatNum;
            }
            foreach($propertyValueArray as $propertyValue){
                $propertyValueRepeatNumMap[$propertyValue["value_id"]]=1;
            }
        }
        $docs = array();
        for($i = 0; $i<$indexCount; $i++){
            $docs[$i] = array(
                "index" => $index,
                "type" => $type,
                "body" => array(
                    "doc" => array(
                        "pid" => $product["pid"],
                        "pname" => $product["pname"],
                        "firstcid" => $product["firstcid"],
                        "secondcid" => $product["secondcid"],
                        "status" => $product["status"],
                        "start_time" => $product["start_time"],
                        "end_time" => $product["end_time"],
                        "inventory_item_id" => $product["inventory_item_id"],
                        "price" => $product["price"],
                        "member_price" => $product["member_price"],
                        "pimg" => $product["pimg"]
                    )
                )
            );
        }
        foreach($propertyArray as $propertyValueArray){
            for($index=0; $index<count($propertyValueArray); $index++){
                $propertyValueRepeatNum = $propertyValueRepeatNumMap[$propertyValueArray[$index]["value_id"]];
                $p = $index * $propertyValueRepeatNum;
                $pPre = $p+$propertyValueRepeatNum;
                while($p<$indexCount){
                    if($p==$pPre){
                        $p += (count($propertyValueArray)-1)*$propertyValueRepeatNum;
                        $pPre = $p+$propertyValueRepeatNum;
                    }else{
                        $doc = $docs[$p];
                        $doc["body"]["doc"][$propertyValueArray[$index]["property_id"]]=$propertyValueArray[$index]["value_id"];
                        $docs[$p] = $doc;
                        $p++;
                    }
                }
            }
        }
        foreach($docs as $doc){
            $this->elasticSearchClient->create($doc);
        }
    }

    //        $params['body']['query']['filtered']["filter"]["and"][]["or"][]["term"]["1"]="2";
    function getProductList(){
        $archiveUsers = M("archiveUser")->where(array("userid"=>$this->userid))->select();
        $index = "product";
        $type="productArchive";
        $params["index"]=$index;
        $params["type"]=$type;
        $propertyMap=array();
        $member=D("Member")->getUserIfMember($this->userid);
        $propertyMap["userType"] = array();
        if($member){
            array_push($propertyMap["userType"], "usertype1");
        }else{
            array_push($propertyMap["userType"], "usertype0");
        }
        if($this->isNewMember()){
            array_push($propertyMap["userType"], "usertype3");
        }

        $subscribes = D("UsersProductsCategorySubscribe")->getByUserId($this->userid);
        if(count($subscribes) == 0){
            $this->error("尚未订阅任何分类，请订阅!");
        }
        $propertyMap["firstcid"] = array();
        for($i=0; $i<count($subscribes); $i++){
            array_push($propertyMap["firstcid"], $subscribes[$i]["product_category_id"]);
        }

        foreach($archiveUsers as $archiveUser){
            if(!$propertyMap[$archiveUser["property_id"]]){
                $propertyMap[$archiveUser["property_id"]]=array();
            }
            array_push($propertyMap[$archiveUser["property_id"]], $archiveUser["value_id"]);
        }
        foreach($propertyMap as $propertyId=>$valueArray){
            $orArray = array();
            foreach($valueArray as $index=>$value){
                $orArray[$index]["term"][$propertyId]=$value;
            }
            $params['body']['query']['filtered']["filter"]["and"][]["or"]=$orArray;
        }
        $params['body']['facets']["filters"]["terms"]=array("field"=>"pid","size"=>0);
//        $params['body']["aggs"]["grouped_by_pid"]["terms"]=array("field"=>"pid","size"=>0);
        $params['body']["from"]=0;
        $params['body']["size"]=10;

        $result = $this->elasticSearchClient->search($params);
        $this->ajaxReturn($result, "JSON");
    }


    function getProductList1(){
        $propertyMap=array();
        $subscribes = D("UsersProductsCategorySubscribe")->getByUserId($this->userid);
        if(count($subscribes) == 0){
            $this->error("尚未订阅任何分类，请订阅!");
        }
        $propertyMap["firstcid"]["type"]= "single";
        $propertyMap["firstcid"]["conditions"]= array();
        for($i=0; $i<count($subscribes); $i++){
            array_push($propertyMap["firstcid"]["conditions"], $subscribes[$i]["product_category_id"]);
        }


        $archiveUsers = M("archiveUser")->where(array("userid"=>$this->userid))->select();
        $index = "product";
        $type="productArchive";
        $params["index"]=$index;
        $params["type"]=$type;


        $member=D("Member")->getUserIfMember($this->userid);
        $propertyMap["user_type"]["type"] = "multiple";
        $propertyMap["user_type"]["conditions"] = array();
        if($member){
            array_push($propertyMap["user_type"]["conditions"], "1");
        }else{
            array_push($propertyMap["user_type"]["conditions"], "0");
        }
        if($this->isNewMember()){
            array_push($propertyMap["user_type"]["conditions"], "3");
        }

        foreach($archiveUsers as $archiveUser){
            $propertyId = "property".$archiveUser["property_id"];
            if(!$propertyMap[$propertyId]){
                $propertyMap[$propertyId]["type"] = "multiple";
                $propertyMap[$propertyId]["conditions"] = array();
            }
            array_push($propertyMap[$propertyId]["conditions"], $archiveUser["value_id"]);
        }

        foreach($propertyMap as $propertyId=>$property){
            $type = $property["type"];
            $conditions = $property["conditions"];
            $orArray = array();
            foreach($conditions as $index=>$value){
                if($type == "single"){
                    $orArray[$index]["term"][$propertyId]=$value;
                }
                if($type == "multiple"){
                    $orArray[$index]["term"][$propertyId."-".$value]="1";
                }

            }
            $params['body']['query']['filtered']["filter"]["and"][]["or"]=$orArray;
        }
        $params['body']["from"]=0;
        $params['body']["size"]=10;

//        $params1['body']['query']['filtered']["filter"]["and"][]["or"][]["term"]["property1-1"]="1";
        $result = $this->elasticSearchClient->search($params);
        $this->ajaxReturn($result, "JSON");
    }

    private function createIndex($pid){
        $archiveProductModel = M("ArchiveProduct");
        $index = "product";
        $type="productArchive";
        $ret = $this->elasticSearchClient->indices()->exists(array("index"=>$index, 'ignore' => 404));
        if(!$ret){
            $this->elasticSearchClient->indices()->create(array("index"=>$index));
        }
        $archiveProducts = $archiveProductModel->where(array("pid"=>$pid))->select();
        foreach($archiveProducts as $archiveProduct){
            $existParam = array(
                "index" => $index,
                "type" => $type,
                "id" => $archiveProduct["pid"] . "-" . $archiveProduct["group"]);
            if($this->elasticSearchClient->exists($existParam)){
                $this->elasticSearchClient->delete($existParam);
            }
            $resturn = $this->createDoc($archiveProduct, $index, $type);
        }
        $this->ajaxReturn($resturn, "JSON");
    }

    function createAll(){
        $archiveProductModel = M("ArchiveProduct");
        $index = "product";
        $type="productArchive";
        try{
            $this->elasticSearchClient->indices()->delete(array("index"=>$index));
        }catch (Exception $e){
        }
        $this->elasticSearchClient->indices()->create(array("index"=>$index));
        $archiveProducts = $archiveProductModel->select();
        foreach($archiveProducts as $archiveProduct){
            $this->createDoc($archiveProduct, $index, $type);
        }
        $this->ajaxReturn(array("result"=>"ok"), "JSON");
    }

    /**
     * @param $archiveProduct
     * @param $productsModel
     * @param $index
     * @param $type
     */
    private  function createDoc($archiveProduct, $index, $type) {
        $productsModel = D("Products");
        $productId = $archiveProduct["pid"];
        $product = $productsModel->getByPid($productId);
        $userTypes = $product["user_type"];
        if($userTypes){
            $userTypes = split(",", $userTypes);
            sort($userTypes);
            $userTypes = join("|", $userTypes);
        }

        $doc = array(
            "index" => $index,
            "type" => $type,
            "id" => $product["pid"] . "-" . $archiveProduct["group"],
            "body" => array(
                "doc" => array(
                    "pid" => $product["pid"],
                    "pname" => $product["pname"],
                    "firstcid" => $product["firstcid"],
                    "secondcid" => $product["secondcid"],
                    "status" => $product["status"],
                    "start_time" => $product["start_time"],
                    "end_time" => $product["end_time"],
                    "inventory_item_id" => $product["inventory_item_id"],
                    "price" => $product["price"],
                    "member_price" => $product["member_price"],
                    "pimg" => $product["pimg"],
                    "userType" => $userTypes,
                    $archiveProduct["property_id"] => $archiveProduct["value_id"]
                )
            )
        );
        $existParam = array(
            "index" => $index,
            "type" => $type,
            "id" => $product["pid"] . "-" . $archiveProduct["group"]);
        try{
            $this->elasticSearchClient->get($existParam);
            $this->elasticSearchClient->update($doc);
        }catch (Exception $e){
            $this->elasticSearchClient->create($doc);
        }
    }

    public function clear(){
        try{
            $this->elasticSearchClient->indices()->delete(array("index"=>"product"));
        }catch (Exception $e){}
        $this->elasticSearchClient->indices()->create(array("index"=>"product"));
    }

}