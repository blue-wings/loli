<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 8/19/14
 * Time: 6:16 PM
 */
class archiveAction extends commonAction{

    private static $INDEX="product";
    private static $TYPE="productArchive";
    private static $PROPERTY_INDEX_PREFIX="property";
    private static $USER_TYPE_INDEX_PREFIX="user_type";

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
        $this->createProductIndex($pid);
        $this->redirect("archive/productProperties", array("pid"=>$pid));
    }

    private function createProductIndex($pid){
        $product = D("Products")->getByPid($pid);
        $index = self::$INDEX;
        $type=self::$TYPE;
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
        $bodyArray = array(
            "pid" => (int)$product["pid"],
            "pname" => $product["pname"],
            "firstcid" => (int)$product["firstcid"],
            "secondcid" => (int)$product["secondcid"],
            "status" => (int)$product["status"],
            "start_time" => $product["start_time"],
            "end_time" => $product["end_time"],
            "inventory_item_id" => (int)$product["inventory_item_id"],
            "inventory_remain"=>($product["inventory"]-$product["inventoryreduced"]),
            "price" => (int)$product["price"],
            "member_price" => (int)$product["member_price"],
            "pimg" => $product["pimg"]
        );
        foreach($propertyArray as $propertyId => $propertyValueArray){
            $propertyParams = array();
            foreach($propertyValueArray  as $propertyValue){
                $valueId = $propertyValue["value_id"];
                $propertyParams[$propertyId."-".$valueId]=1;
            }
            $bodyArray = array_merge($bodyArray, $propertyParams);
        }
        $doc = array(
            "index" => $index,
            "type" => $type,
            "id" => $product["pid"],
            "body" =>$bodyArray
        );
        $this->elasticSearchClient->create($doc);
    }

    //$params['body']['query']['filtered']["filter"]["and"][]["or"][]["term"]["1"]="2";
    function getProductList(){

        $subscribes = D("UsersProductsCategorySubscribe")->getByUserId($this->userid);
        if(!$subscribes || count($subscribes) == 0){
            $this->error("尚未订阅任何分类，请订阅!");
        }

        $archiveUsers = M("archiveUser")->where(array("userid"=>$this->userid))->select();
        $index = self::$INDEX;
        $type=self::$TYPE;
        $params["index"]=$index;
        $params["type"]=$type;
        $propertyMap=array();
        $member=D("Member")->getUserIfMember($this->userid);
        $propertyMap["userType"] = array();
        if($member){
            $propertyMap["userType"][self::$USER_TYPE_INDEX_PREFIX."-1"]=1;
        }else{
            $propertyMap["userType"][self::$USER_TYPE_INDEX_PREFIX."-0"]=1;
        }
        if($this->isNewMember()){
            $propertyMap["userType"][self::$USER_TYPE_INDEX_PREFIX."-3"]=1;
        }

        foreach($archiveUsers as $archiveUser){
            $propertyMap[$archiveUser["property_id"]][self::$PROPERTY_INDEX_PREFIX.$archiveUser["property_id"]."-".$archiveUser["value_id"]]=1;
        }

        foreach($propertyMap as $propertyId=>$valueMap){
            foreach($valueMap as $key=>$value){
                $orArray["term"][$key]=$value;
            }
            $params['body']['query']['filtered']["filter"]["and"][]["or"][]=$orArray;
        }

        foreach($subscribes as $subscribe){
            $subscribeArray["term"]["firstcid"]=$subscribe["product_category_id"];
        }
        $params['body']['query']['filtered']["filter"]["and"][]["or"][]=$subscribeArray;
        $params['body']['query']['filtered']["filter"]["and"][]["or"][]=["range"=>["inventory_remain"=>["gt"=>0]]];
        $params['body']['query']['filtered']["filter"]["and"][]["or"][]=["term"=>["status"=>C("PRODUCT_STATUS_PUBLISHED")]];
        $params['body']['query']['filtered']["filter"]["and"][]["or"][]=["range"=>["end_time"=>["gt"=>date("Y-m-d H:i:s",time())]]];

        $params['body']['facets']["filters"]["terms"]=array("field"=>"pid","size"=>0);
        $params['body']["aggs"]["grouped_by_pid"]["terms"]=array("field"=>"pid","size"=>0);
        $params['body']["from"]=0;
        $params['body']["size"]=10;

        $result = $this->elasticSearchClient->search($params);
        $this->ajaxReturn($result, "JSON");
    }

    public function init(){
        $params = [
            'index' => self::$INDEX,
            'body' => [
                'settings' => [
                    'number_of_shards' => 5,
                    'number_of_replicas' => 1,
                    'analysis' => [
                        'filter' => [
                            'shingle' => [
                                'type' => 'shingle'
                            ]
                        ],
                        'char_filter' => [
                            'pre_negs' => [
                                'type' => 'pattern_replace',
                                'pattern' => '(\\w+)\\s+((?i:never|no|nothing|nowhere|noone|none|not|havent|hasnt|hadnt|cant|couldnt|shouldnt|wont|wouldnt|dont|doesnt|didnt|isnt|arent|aint))\\b',
                                'replacement' => '~$1 $2'
                            ],
                            'post_negs' => [
                                'type' => 'pattern_replace',
                                'pattern' => '\\b((?i:never|no|nothing|nowhere|noone|none|not|havent|hasnt|hadnt|cant|couldnt|shouldnt|wont|wouldnt|dont|doesnt|didnt|isnt|arent|aint))\\s+(\\w+)',
                                'replacement' => '$1 ~$2'
                            ]
                        ],
                        'analyzer' => [
                            'product' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'stop', 'kstem']
                            ]
                        ]
                    ]
                ],
                'mappings' => [
                    self::$TYPE => [
                        "_all" => ["enabled"=>false],
                        "_source"=>[
                            "enabled"=>true,
                            "compress" => true
                        ],
                        "dynamic"=>"true",
                        'properties' => [
                            'pname' => [
                                'type' => 'string',
                                'analyzer' => 'product',
                                'store' => true
                            ],
                            'readme' => [
                                'type' => 'string',
                                'analyzer' => 'product',
                                'store' => true
                            ],
                            'pid' => [
                                'type' => 'integer',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'firstcid' => [
                                'type' => 'integer',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'secondcid' => [
                                'type' => 'integer',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'start_time' => [
                                'type' => 'date',
                                "format"=> "YYYY-MM-dd HH:mm:ss",
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'end_time' => [
                                'type' => 'date',
                                "format"=> "YYYY-MM-dd HH:mm:ss",
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'price' => [
                                'type' => 'long',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'member_price' => [
                                'type' => 'long',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'pimg' => [
                                'type' => 'string',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'status' => [
                                'type' => 'integer',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'effectcid' => [
                                'type' => 'integer',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'brandcid' => [
                                'type' => 'integer',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'inventory_item_id' => [
                                'type' => 'integer',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'inventory_remain' => [
                                'type' => 'integer',
                                'index' => 'not_analyzed',
                                'store' => false
                            ],
                            'buyurl' => [
                                'type' => 'string',
                                'index' => 'not_analyzed',
                                'store' => false
                            ]
                        ],
                        "dynamic_templates"=>[
                            [
                                "property"=>[
                                    "match"=>"property*",
                                    "match_mapping_type"=>"integer",
                                    "mapping"=>[
                                        'type' => 'string',
                                        'index' => 'not_analyzed',
                                        'store' => false
                                    ]
                                ]
                            ],
                            [
                                "property"=>[
                                    "match"=>"user_type*",
                                    "match_mapping_type"=>"integer",
                                    "mapping"=>[
                                        'type' => 'integer',
                                        'index' => 'not_analyzed',
                                        'store' => false
                                    ]
                                ]
                            ],
                        ]
                    ]
                ]
            ]
        ];
        try{
            $this->elasticSearchClient->indices()->delete(array("index"=>"product"));
        }catch (Exception $e){}
        $this->elasticSearchClient->indices()->create($params);
    }


}