<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 9/18/14
 * Time: 10:58 AM
 */
class ArchiveIndexModel extends Model {

    private static $INDEX="product";
    private static $TYPE="productArchive";
    private static $PROPERTY_INDEX_PREFIX="property";
    private static $USER_TYPE_INDEX_PREFIX="user_type";

    private  $elasticSearchClient;

    function _initialize() {
        require 'vendor/autoload.php';
        $params = array();
        $params['hosts'] = array (
            C("elastic_search_host")
        );
        $this->elasticSearchClient = new Elasticsearch\Client($params);
        parent::_initialize();
    }

    public function createProductsIndex($pids){
        foreach($pids as $pid){
            $this->createProductIndex($pid);
        }
    }

    public function createProductIndex($pid){
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

    public function getSubscribedProductsCount($userId, $productType, $searchWords){
        $subscribes = D("UsersProductsCategorySubscribe")->getByUserId($userId);
        if(!$subscribes || count($subscribes) == 0){
            return false;
        }
        $params = $this->prepareParams($userId, $productType, $subscribes, $searchWords);
        $count = $this->elasticSearchClient->count($params);
        if(!$count){
            return 0;
        }
        return $count["count"];
    }

    //$params['body']['query']['filtered']["filter"]["and"][]["or"][]["term"]["1"]="2";
    public function getSubscribedProductList($userId, $productType, $from, $rows, $searchWords){
        $subscribes = D("UsersProductsCategorySubscribe")->getByUserId($userId);
        if(!$subscribes || count($subscribes) == 0){
            return false;
        }
        $params = $this->prepareParams($userId, $productType, $subscribes, $searchWords);
        $params['body']["_source"]=["pid","pname","price","member_price","pimg","max_peruser"];
        $params['body']["from"]=0;
        $params['body']["size"]=10;
        $params['body']['facets']["filters"]["terms"] = array("field" => "pid", "size" => 0);
        $params['body']["aggs"]["grouped_by_pid"]["terms"] = array("field" => "pid", "size" => 0);
        $params['body']['highlight']=["pre_tags"=>["<h1>"],"post_tags"=>["</h1>"],"fields"=>["pname"=>[]]];
        $params['body']["sort"][]["start_time"]=["order"=>"desc"];
        if($from){
            $params['body']["from"]=$from;
        }
        if($rows){
            $params['body']["size"]=$rows;
        }
        $products = array();
        $result = $this->elasticSearchClient->search($params);
        $count = $result["hits"]["total"];
        foreach($result["hits"]["hits"] as $hit){
            $source = $hit["_source"];
            array_push($products, $source);
        }
        $ret["count"]=$count;
        $ret["products"]=$products;
        return $ret;
    }

    public function initIndex(){
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
                            'ik' => [
                                "alias"=>['ik_analyzer'],
                                "type"=>"org.elasticsearch.index.analysis.IkAnalyzerProvider"
                            ],
                            'ik_max_word' => [
                                "use_smart"=>false,
                                "type"=>"ik"
                            ],
                            'ik_smart' => [
                                "use_smart"=>true,
                                "type"=>"ik"
                            ],
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
                                'analyzer' => 'ik',
                                'store' => true
                            ],
                            'readme' => [
                                'type' => 'string',
                                'analyzer' => 'ik',
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
                                        'type' => 'integer',
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
            $this->elasticSearchClient->indices()->delete(array("index"=>self::$INDEX));
        }catch (Exception $e){}
        $this->elasticSearchClient->indices()->create($params);
    }

    /**
     * @param $userId
     * @param $subscribes
     * @return mixed
     */
    private  function prepareParams($userId, $productType, $subscribes, $searchWords)
    {
        $archiveUsers = M("archiveUser")->where(array("userid" => $userId))->select();
        $index = self::$INDEX;
        $type = self::$TYPE;
        $params["index"] = $index;
        $params["type"] = $type;
        $propertyMap = array();
        foreach ($archiveUsers as $archiveUser) {
            $propertyMap[$archiveUser["property_id"]][self::$PROPERTY_INDEX_PREFIX . $archiveUser["property_id"] . "-" . $archiveUser["value_id"]] = 1;
        }

        foreach ($propertyMap as $propertyId => $valueMap) {
            foreach ($valueMap as $key => $value) {
                $orArray["term"][$key] = $value;
            }
            $params['body']['query']['filtered']["filter"]["and"][]["or"][] = $orArray;
        }

        foreach ($subscribes as $subscribe) {
            $subscribeArray["term"]["firstcid"] = $subscribe["product_category_id"];
        }
        $params['body']['query']['filtered']["filter"]["and"][]["or"][] = $subscribeArray;
        $params['body']['query']['filtered']["filter"]["and"][]["or"][] = ["term" => [self::$USER_TYPE_INDEX_PREFIX . "-".$productType =>1]];
        $params['body']['query']['filtered']["filter"]["and"][]["or"][] = ["range" => ["inventory_remain" => ["gt" => 0]]];
        $params['body']['query']['filtered']["filter"]["and"][]["or"][] = ["term" => ["status" => C("PRODUCT_STATUS_PUBLISHED")]];
        $params['body']['query']['filtered']["filter"]["and"][]["or"][] = ["range" => ["end_time" => ["gt" => date("Y-m-d H:i:s", time())]]];
        if($searchWords){
            $params['body']['query']['filtered']['query']["query_string"]=["fields"=>["pname","readme"], "query"=>$searchWords];
        }
        return $params;
    }

}