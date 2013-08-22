<?php 
// include 'chromephp-master/ChromePhp.php';
includePackage ('SolrDataAggregation');
class CatalogueBrowserWebModule extends WebModule
{
    protected $id='CatalogueBrowser';

    //datahubs url
    protected $SMARTSTREETS_URL='http://smartstreets.sensetecnic.com';
    // protected $SELECTED_DATAHUB = "";

    protected function initialize(){
    	//set up data structures and js libs
        // ChromePhp::log ("SELECTED_DATAHUB");
    }

    protected function initializeForPage() {

    	$this->addExternalJavascript('http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js');
    	$this->addExternalJavascript('http://code.jquery.com/ui/1.10.3/jquery-ui.js');
    	$this->addExternalCSS('http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css');

        $this->assign('message', 'Catalogue Browser');

        //instantiate controller 
        $this->controller = DataRetriever::factory('InteropDataRetriever', array());
        $CatalogueItemSolrController = DataRetriever::factory('CatalogueItemSolrDataRetriever', array());

        switch ($this->page) 
        { 
            case 'index': 

                //populate datahubs in page
                $datahub_array = $this -> getModuleSection ("datahubs", "datahub");
                $select_options_array = array();
                foreach ($datahub_array as $key => $val){     
                    $select_options_array[] = $key;
                }

                $this->assign('datahub_array', $select_options_array);

                break; 

            case 'datahub':


                //TODO: make a page for selecting datahubs
                $SELECTED_DATAHUB = $this->getArg('datahub');
                // $SELECTED_DATAHUB = "Smartstreets";
                $baseURL= $this->getModuleVar('BASE_URL', strtolower($SELECTED_DATAHUB),"datahub");

                
                 //get the  catalogues 
                 $catalogues = $this->controller->getCatalogues($baseURL);

                 //prepare the list of catalogues
                 $catalogueList = array();
                 if ($catalogues!=null){
                    foreach ($catalogues["items"] as $catalogueItem){
                        //each item has "i-object-metadata", and "href"
                        $href = $catalogueItem["href"];
                        // $metadata = json_encode($catalogueItem["i-object-metadata"], true);

                        //check if catalog supports search and has description
                        $description= ""; 
                        $isSearchable = false;
                        foreach ($catalogueItem["i-object-metadata"] as $metaItem){
                            

                            if ($metaItem["rel"]== $this->getModuleVar('Description', strtolower($SELECTED_DATAHUB).":catalogue:rel","datahub")){
                                $description = $metaItem["val"];
                            }
                            if ($metaItem["rel"]== $this->getModuleVar('Support_Search', strtolower($SELECTED_DATAHUB).":catalogue:rel","datahub") && $metaItem["val"]== "urn:X-tsbiot:search:simple"){
                                $isSearchable=true;
                            }
                        }

                        //build view catalogue redirect URL and args to pass 
                        $args = array(
                            'href' => $href,
                            'hub' => $SELECTED_DATAHUB
                        );

                        // create navlist item
                        $catalogue = array (
                            'label'=> $this->getModuleVar($SELECTED_DATAHUB, "datahubs", "datahub")." Hub",
                            'boldLabels'=> true,
                            'title'=> $href,
                            'subtitle' => $description,
                            'url' => $this->buildBreadcrumbURL("viewCatalogue", $args, true)
                        );
                 
                        $catalogueList[]= $catalogue;
                    }
                 }
                 $this->assign('catalogueList', $catalogueList);
                 $this->assign('selectedHub', $SELECTED_DATAHUB);

                 break;

            case 'viewCatalogue':

                
            	$parent_href = $this->getArg('href');
                $SELECTED_DATAHUB =$this->getArg('hub');
            	//set page title
            	$this -> setPageTitle ($parent_href);
            	//query data from smartstreets
                $baseURL= $this->getModuleVar('BASE_URL', strtolower($this->getArg('hub')),"datahub");

                
                $searchURL= $baseURL.$parent_href;
                
                //query solr for parentUrl == searchURL
                $params['parentUrl'] = $searchURL;
                $sort = "";
                //CREATE SOLR SEARCH QUERIES
                $response = SolrSearchResponse::getKeywordSearchResponse($CatalogueItemSolrController, $params, $sort, 0);
                // ChromePhp::log ("Response: ".$response);
                //get catalog details
                $itemParam['href'] = $searchURL;
                $itemResponse = SolrSearchResponse::getKeywordSearchResponse($CatalogueItemSolrController, $itemParam, $sort, 0);
                if ($itemResponse!=null){
                    // ChromePhp::log ("search result: ".$itemResponse);
                    $catInfo=Array();
                    $itemResponse=json_decode($itemResponse, true);
                    //Parse catalogue details to catInfo
                    foreach ($itemResponse["docs"] as $metadata){
                        foreach ($metadata as $key=>$val){
                            $catInfo[]= $key.": ".$val;
                        }
                    }
                }else{
                    //no data! 
                }

                if ($response ==null){
                     // ChromePhp::log ("There is no result");
                }else{
                    //process json and create nav list data
                    $resultList = array();
                    $results = json_decode($response, true);//convert to associative array
                    // ChromePhp::log ("search result: ".$response);

                    foreach($results["docs"] as $item){
                        $resourceURL = "";
                        $url="";
                        $itemSearchURL = "";
                        $href = isset($item["href"]) ? $item["href"] : null;
                        $description= isset($item["hasdescription"]) ? $item["hasdescription"] : null;
                        $id=isset($item["id"]) ? $item["id"] : null;
                        $itemId=isset($item["hasid"]) ? $item["hasid"] : null;
                        $name = isset($item["name"]) ? $item["name"] : null;
                        $title = isset($item["title"]) ? $item["title"] : null;
                        $maintainer = isset($item["maintainer"]) ? $item["maintainer"] : null;
                        $lastupdate = isset($item["lastupdate"]) ? $item["lastupdate"] : null;
                        $isCatalogue = isset($item["isCatalogue"]) ? $item["isCatalogue"] : null;
                        // $url=isset($item["hasDescription"]) ? $item["hasDescription"] : null;
                        $parentURL=isset($item["parentUrl"]) ? $item["parentUrl"] : null;
                        $datahub=isset($item["datahub"]) ? $item["datahub"] : null;
                        $tags= isset($item["tags"]) ? $item["tags"] : null;
                        $tagArray=null;

                        if ($tags!=null)
                            $tagArray = explode(',', $tags);
                        else
                             $tagArray =null;

                        if ($isCatalogue){
                            // open to another view catalogue page 
                            $sub_href = substr($href, strpos($href, "/cat"));

                            //build view catalogue redirect URL and args to pass 
                            $args = array(
                                'id' => $itemId,
                                'href' => $sub_href,
                                'hub' => $datahub
                            );
                            $url = $this->buildBreadcrumbURL("viewCatalogue", $args, true);
                        }else{

                            //create resource download url
                            //check  if href contains "Http", if not, append to current url
                            if (strpos($href, "http")!==false){
                                $resourceURL = $href;
                                // ChromePhp::log ("full url!: ".$href);
                            }else{
                                $resourceURL = $baseURL.$href;
                                // ChromePhp::log ("part url! ".$href);
                            }
                        }

                        //set itemSearchURl
                        if ($itemId!= null){
                            //create search URL//urn:X-smartstreets:rels:hasId
                            $itemSearchURL =$parentURL."?rel=urn:X-".$datahub.":rels:hasId"."&val=".$itemId;    
                        }else{
                             $itemSearchURL =null;
                        }

                        $type="";
                        if($isCatalogue=="true"){
                            $type = "Catalogue";
                        }else{
                            $type = "Item";
                        }
                        // create navlist item
                        $itemData = array (
                            'lastupdate'=>$lastupdate,
                            'label'=> $name,
                            'boldLabels'=> true,
                            'maintainer'=> $maintainer,
                            'subtitle' => $description,
                            'url' => $url,
                            'resourceURL' => $resourceURL,
                            'itemSearchURL'=> $itemSearchURL,
                            'badge'=>$tagArray,
                            'type'=>$type

                        );
                 
                        $resultList[]= $itemData;
                    }
                    //populate search parameters for load more posts
                    // $searchParam["parentUrl"]=$searchURL;
                    
                    $this->assign('itemList', $resultList);
                    $this->assign('itemNum', $results["numFound"]);
                    $this->assign ('catalogueURL', $searchURL);
                    $this->assign('catalogueInfo', $catInfo);
                    $this->assign('searchParam', $searchURL);
                    $this->assign('index', 10);
                }


                // $catItems = $this->controller->getCatalogueItems($baseURL, $parent_href);

                // //prepare the list of catalogues
                // $itemList = array();
                // $json= json_encode($catItems, true);

                // if ($catItems["item-metadata"]){

                //  	//it is a catalogue 
                //  	// dispaly catalogue metadata details
                //  	$catMetadata = array();
	               //  foreach ($catItems["item-metadata"] as $metadata){

	               //  	$rel = $metadata["rel"];
	               //  	$val = $metadata["val"];

                //         $rel_array = $this->getModuleSection(strtolower($SELECTED_DATAHUB).":catalogue:rel", "datahub");
                //         foreach ($rel_array as $key => $meta_val){                    
                //             if ($rel == $meta_val){
                //                 $key = str_replace ("_", " ", $key);//replace '_' with space
                //                 $catMetadata[] = $key.": ".$val;
                //             }
                //         }	           
	               //  }  
	               //  // ChromePhp::log('Cat info: '.implode("\n", $catMetadata));
	               //  $this->assign('catalogueInfo', $catMetadata);
                // }

                
                // foreach ($catItems["items"] as $item){
                // 	//each item has "i-object-metadata", and "href"
                //  	$href = $item["href"];
                //  	$metadata = json_encode($item["i-object-metadata"], TRUE);


                //  	//check if catalog supports search and has description
                //  	$description= "No Description"; 
                //  	$id="";
                //  	$name = "No Name";
                //  	$title = "";
                //  	$maintainer = "";
                //  	$isSearchable = 0;
                //  	$isCatalogue = 0;
                //     $lastupdate ="";
                //     $tags ="";
                 

                //  	$url="";
                //  	$resourceURL="";
                //  	$itemSearchURL="";

                //  	foreach ($item["i-object-metadata"] as $metaItem){


                //         $rel = $metaItem["rel"];
                //         $val = $metaItem["val"];

                //         // $item_rel_array = $this->getModuleSection($SELECTED_DATAHUB.":item:rel", "datahub");
                //         // foreach ($item_rel_array as $key => $meta_val){                    
                //         //     if ($rel == $meta_val){
                //         //         $catMetadata[] = $key.": ".$val;
                //         //     }
                //         // }

                //         switch ($rel){
                //             case $this->getModuleVar('Description', strtolower($SELECTED_DATAHUB).":item:rel", 'datahub'):
                //                 if ($val !="")
                //                     $description = $val;
                //                 break;
                //             case $this->getModuleVar('Support_Search', strtolower($SELECTED_DATAHUB).":item:rel", 'datahub'):
                //                 if ($val =="urn:X-tsbiot:search:simple")
                //                     $isSearchable=1;
                //                 break;
                //             case $this->getModuleVar('ID', strtolower($SELECTED_DATAHUB).":item:rel", 'datahub'):
                //                 if ($val !=""){
                //                     $id = $val;
                //                     $itemSearchURL =$baseURL.$parent_href."?rel=".$rel."&val=".$id;
                //                 }
                //                 break;
                //             case $this->getModuleVar('Name', strtolower($SELECTED_DATAHUB).":item:rel", 'datahub'):
                //                 if ($val !="")
                //                     $name = $val;
                //                 break;
                //             case $this->getModuleVar('Title', strtolower($SELECTED_DATAHUB).":item:rel", 'datahub'):
                //                 if ($val !="")
                //                     $title = $val;
                //                 break;
                //             case $this->getModuleVar('Maintainer', strtolower($SELECTED_DATAHUB).":item:rel", 'datahub'):
                //                 if ($val !="")
                //                     $maintainer = $val;
                //                 break;
                //             case $this->getModuleVar('Content_Type', strtolower($SELECTED_DATAHUB).":item:rel", 'datahub'):
                //                 if ($val =="application/vnd.tsbiot.catalogue+json")
                //                     $isCatalogue=1;
                //                 break;
                //             case $this->getModuleVar('Last_Updated', strtolower($SELECTED_DATAHUB).":item:rel", 'datahub'):
                //                 if ($val !="")
                //                     $lastupdate=$val;
                //                 break;
                //             case $this->getModuleVar('Tags', strtolower($SELECTED_DATAHUB).":item:rel", 'datahub'):
                //                 if ($val !="")
                //                     $tags=$val;
                //                 break;

                //         }

                //         // ChromePhp::log('metaItem: '.json_encode($metaItem));
                //  	}

                 	
                //  	//build view catalogue redirect URL and args to pass 
                //  	$args = array(
                //  		'id' => $id,
                //  		'href' => $href,
                //         'hub' => $SELECTED_DATAHUB
                //  	);

                 	
                //  	//check if it is an item or catalogue
                //     $resourceURL ="";
                //  	if ($isCatalogue){
                //  		// open to another view catalogue page 
                //  		$url = $this->buildBreadcrumbURL("viewCatalogue", $args, true);
                //  		// ChromePhp::log('DRILL DOWN');
                //  	}else{
                //  		//create resource download url
                //         //check  if href contains "Http", if not, append to current url
                //         if (strpos($href, "http")!==false){
                //             $resourceURL = $href;
                //             // ChromePhp::log ("full url!: ".$href);
                //         }else{
                //             $resourceURL = $baseURL.$href;
                //             // ChromePhp::log ("part url! ".$href);
                //         }
                 		
                //  	}

                //     $tag_array = "";
                //     if ($tags!=null)
                //         $tag_array = explode(',', $tags);                    

                //  	// create navlist item
                //  	$itemData = array (
                //         'lastupdate'=>$lastupdate,
                //  		'label'=> $name,
                //  		'boldLabels'=> true,
                //  		'title'=> $title,
                //  		'subtitle' => $description,
                //  		'url' => $url,
                //  		'resourceURL' => $resourceURL,
                //  		'itemSearchURL'=> $itemSearchURL,
                //         'badge'=>$tag_array
                //  	);
            
                //  	$itemList[]= $itemData;
                // }

                // $this->assign('itemList', $itemList);

            	 break;

            case 'search':

                $datahub_array = $this -> getModuleSection ("datahubs", "datahub");

                $select_options_array = array();
                $select_options_array [""]= "None";
                foreach ($datahub_array as $key => $val){     
                    $hub =$key;              
                    $hub_url = $this-> getModuleVar('BASE_URL', strtolower($key),"datahub");   //get base url from datahub.ini file using $key as section
                    $hub_name = $val;   // assign $val as select's option name

                    $select_options_array[$hub_url] = $hub;
                }
                // ChromePhp::log ($select_options_array);
                $this->assign('select_options_array', $select_options_array);
                $this->assign('mySelect', "");
                break;

           	case 'searchResults':
                //set page title
                $this -> setPageTitle ("Search Results");
           		//create array of params
                $params= array();
                // $params['datahub'] = strtolower($this->getArg('datahub'));
                // $params['datahub'] = strtolower($this->getArg('hub_selected'));
                // $params['catalogue_href'] = $this->getArg('catalogue');
                $params['iscontenttype'] = $this->getArg('content_type');
                $params['id']= $this->getArg('id');
                $params['hasdescription'] = $this->getArg('description');
                $params['name'] = $this->getArg('name');
                $params['tags'] = $this->getArg('tags');
                $params['maintainer'] = $this->getArg('maintainer');
                $params['owner'] = $this->getArg('owner');
                $params['organization'] = $this->getArg('organization');
                $params['license'] = $this->getArg('license');
                $sort = $this->getArg('sort');

                // $baseURL= $this->getModuleVar('BASE_URL', strtolower($params['datahub']),"datahub");

                //CREATE SOLR SEARCH QUERIES
                $response = SolrSearchResponse::getKeywordSearchResponse($CatalogueItemSolrController, $params, $sort, 0);
                
                

                // TODO: check that the json returned is not NULL .
                if ($response ==null){
                     // ChromePhp::log ("There is no result");
                }else{
                    //process json and create nav list data
                    $resultList = array();
                    $results = json_decode($response, true);//convert to associative array

                    foreach($results["docs"] as $item){
                        $resourceURL = "";
                        $url="";
                        $itemSearchURL = "";
                        $href = isset($item["href"]) ? $item["href"] : null;
                        $description= isset($item["hasdescription"]) ? $item["hasdescription"] : null;
                        $id=isset($item["id"]) ? $item["id"] : null;
                        $itemId=isset($item["hasid"]) ? $item["hasid"] : null;
                        $name = isset($item["name"]) ? $item["name"] : "No Name";
                        $title = isset($item["title"]) ? $item["title"] : "No Title";
                        $maintainer = isset($item["maintainer"]) ? $item["maintainer"] : null;
                        $lastupdate = isset($item["lastupdate"]) ? $item["lastupdate"] : null;
                        // $isSearchable = isset($item["hasDescription"]) ? $item["hasDescription"] : null;
                        $isCatalogue = isset($item["isCatalogue"]) ? $item["isCatalogue"] : null;
                        // $url=isset($item["hasDescription"]) ? $item["hasDescription"] : null;
                        $parentURL=isset($item["parentUrl"]) ? $item["parentUrl"] : null;
                        $datahub=isset($item["datahub"]) ? $item["datahub"] : null;
                        $tags= isset($item["tags"]) ? $item["tags"] : null;
                        $tagArray=null;

                        if ($tags!=null)
                            $tagArray = explode(',', $tags);
                        else
                            $tagArray =null;
                        // $itemSearchURL=isset($item["hasDescription"]) ? $item["hasDescription"] : null;
                        $baseURL= $this->getModuleVar('BASE_URL', strtolower($datahub),"datahub");

                        if ($isCatalogue){
                            // open to another view catalogue page 
                            $sub_href = substr($href, strpos($href, "/cat"));

                            //build view catalogue redirect URL and args to pass 
                            $args = array(
                                'id' => $itemId,
                                'href' => $sub_href,
                                'hub' => $datahub
                            );
                            $url = $this->buildBreadcrumbURL("viewCatalogue", $args, true);
                        }else{

                            //create resource download url
                            //check  if href contains "Http", if not, append to current url
                            if (strpos($href, "http")!==false){
                                $resourceURL = $href;
                                // ChromePhp::log ("full url!: ".$href);
                            }else{
                                $resourceURL = $baseURL.$href;
                                // ChromePhp::log ("part url! ".$href);
                            }
                        }

                        //set itemSearchURl
                        if ($itemId!= null){
                            //create search URL//urn:X-smartstreets:rels:hasId
                            $itemSearchURL =$parentURL."?rel=urn:X-".$datahub.":rels:hasId"."&val=".$itemId;    
                        }else{
                             $itemSearchURL =null;
                        }

                        $type="";
                        if($isCatalogue=="true"){
                            $type = "Catalogue";
                        }else{
                            $type = "Item";
                        }

                        // create navlist item
                        $itemData = array (
                            'lastupdate'=>$lastupdate,
                            'label'=> $name,
                            'boldLabels'=> true,
                            'maintainer'=> $maintainer,
                            'subtitle' => $description,
                            'url' => $url,
                            'resourceURL' => $resourceURL,
                            'itemSearchURL'=> $itemSearchURL,
                            'badge'=>$tagArray,
                            'type'=>$type
                        );
                 
                        $resultList[]= $itemData;
                    }

                    //fix: replace space with &nbps for css data attribute
                    $params["tags"]=str_replace(" ","&#160;",$params["tags"]);

                    $searchParam=json_encode($params);
                    $this->assign('itemList', $resultList);
                    $this->assign ('resultCount', $results["numFound"]);
                    $this->assign('searchParam', $searchParam);
                    $this->assign('index', 10);
                    $this->assign('sort', $sort);
                }


                break;

        } 
    }
}
