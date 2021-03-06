<?php 
ini_set("memory_limit","-1");
includePackage ('SolrDataAggregation');
class TrafficExplorerAPIModule extends APIModule
{
    protected $id='TrafficExplorer';

    //datahubs url
    protected function initializeForCommand() {

        //instantiate controller 
        $this->controller = DataRetriever::factory('InteropDataRetriever', array());
        // $this->mongo= DataRetriever::factory('MongoDataRetriever', array());
        // $this->db = new MongoManager("localhost", "traffic");

        //mongodb initialization
        $dbhost = 'localhost';  
        $dbname = 'test';
        // $dbname = 'traffic';
        $m = new Mongo("mongodb://localhost");  
        $db = $m->$dbname;  

  //       $m = new Mongo("mongodb://94.23.54.103:27017,188.165.219.99:27017,94.23.220.151:27017", array("replicaSet" => "cluster"));
  // var_dump($m);
  // $db = $m->selectDB('my_database');
  // $db->authenticate("my_login", "my_password");
  // $collection = new MongoCollection($db, 'my_collection');
  // $cursor = $collection->find();
  // foreach ($cursor as $doc) { var_dump($doc); }

        switch ($this->command) 
        { 
            case 'getDataResponse': 
                $baseURL = $this->getArg('url');
                $details = $this->controller->getItemDetails($baseURL);

                $this->setResponse($details);
                $this->setResponseVersion(1);
                break;

            case 'getMongoRegions':
                $collection = $this->getArg('collection');
                $cursor = $db->$collection->find();
                $results= json_encode(iterator_to_array($cursor));
                $this->setResponse($results);
                $this->setResponseVersion(1);
                break;

            case 'getEntireMongoCollection':
                $collection = $this->getArg('collection');
                $cursor = $db->$collection->find();
                $results= json_encode(iterator_to_array($cursor));
                $this->setResponse($results);
                $this->setResponseVersion(1);
                break;

            case 'queryMongoBySingleKey':
                //AND QUERIES
                $collection = $this->getArg('collection');
                $query = $this->getArg('query');
                if ($query=="null"){
                    $cursor = $db->$collection->find();
                }else{
                    $query = json_decode($this->getArg('query'));
                    $cursor = $db->$collection->find($query);
                }
                $queryArray= array("query"=>$query);
                $resultsArray=array("results"=>iterator_to_array($cursor));
                $results= json_encode(array_merge($queryArray, $resultsArray));
                $this->setResponse($results);
                $this->setResponseVersion(1);
                break;

            case 'getMongoCorrelation':
                $collection = $this->getArg('collection');
                $query = json_decode($this->getArg('query'));

                //create query here...
                $timeRangeArray=Array();
                $region;
                foreach($query as $key=>$val){
                    if($key=="tf_recordedtime"){
                        foreach($val as $val_key=>$val_val){
                            $timeRangeArray[$val_key]=new MongoDate(strtotime($val_val));
                        }
                    }
                    if ($key=="region"){
                        $region=$val;
                    }
                }
                $queryArray = array(
                    'tf_recordedtime' => $timeRangeArray,
                    'region'=> $region
                );


                $cursor = $db->$collection->find($queryArray);
                $results= json_encode(iterator_to_array($cursor));
                $this->setResponse($results);
                $this->setResponseVersion(1);
                break;

            case 'mongoGeoRadiusSearch':

                $query = json_decode($this->getArg("query"), true);
                $lat = (float)$this->getArg("lat");
                $lng = (float)$this->getArg("lng");
                $radius = (float)$this->getArg("radius");
                $collection=$this->getArg("collection");

                $geojson=array(
                    "type"=>"Point",
                    "coordinates"=>array($lng, $lat)
                );

                $geoRangeArray = array(
                    '$geometry' => $geojson,
                    '$maxDistance'=>$radius

                );
                $queryArray = array(
                    'geo'=> Array('$near' => $geoRangeArray)
                );
                $cursor = $db->$collection->find($queryArray);
                $results= json_encode(iterator_to_array($cursor));
                $this->setResponse($results);
                $this->setResponseVersion(1);

                break;

            case 'getMongoTimeRangeResponse': 
                $collection = $this->getArg('collection');
                $query = json_decode($this->getArg('query'));

                //create query here...
                $daysAgo = new MongoDate(strtotime('-20 days'));

                $timeRangeArray=Array();
                foreach($query as $key=>$val){
                    if($key=="recordedtime"){
                        foreach($val as $val_key=>$val_val){
                            $timeRangeArray[$val_key]=new MongoDate(strtotime($val_val));
                        }
                    }
                }
                $queryArray = array(
                    'recordedtime' => $timeRangeArray
                );
                $cursor = $db->$collection->find($queryArray)->sort(array('value' => -1))->limit(1000);


                $results= json_encode(iterator_to_array($cursor));


                $this->setResponse($results);
                $this->setResponseVersion(1);
                break;
        } 
    }

}