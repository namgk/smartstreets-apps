<?php
/**
 * Contains methods that handle simple search queries
 */
includePackage ('SolrDataAggregation');

class SolrSearchResponse
{ 
 
    public static function getKeywordSearchResponse ($controller, $params, $sort, $offset){

        foreach ($params as $key=>$value)
        $searchQuery = SearchQueryFactory::createKeywordSearchQuery($params);

        
        if ($sort != null){
            $str_split = explode('.', $sort);
            $sortField = $str_split[0];
            $isAscending= ($str_split[1]=='a') ? true:false; 
           
            $searchSort = new SearchSort($sortField, $isAscending);
            $searchQuery->addSort($searchSort);
            
        }
        $searchQuery->setMaxItems(10);//temporary to display all results until scroll to load more is implemented.
        $searchQuery->setStartIndex($offset);
        $searchQuery->addReturnField("title");
        $searchQuery->addReturnField("id");
        $searchQuery->addReturnField("hasid");
        $searchQuery->addReturnField("hasdescription");
        $searchQuery->addReturnField("tags");
        $searchQuery->addReturnField("lastupdate");
        $searchQuery->addReturnField("isCatalogue");
        $searchQuery->addReturnField("href");
        $searchQuery->addReturnField("parentUrl");
        $searchQuery->addReturnField("datahub");
        $searchQuery->addReturnField("name");
        $searchQuery->addReturnField("iscontenttype");
        $searchQuery->addReturnField("maintainer");
        $data = $controller->query($searchQuery, 'OR');

        if (!isset($data["response"])) {
          throw new KurogoDataException("Error, not a valid response.");
        }

        return json_encode ($data["response"]);
    } 	

    public static function getAdvancedSearchResponse ($controller, $params, $sort, $offset){

        foreach ($params as $key=>$value)
        $searchQuery = SearchQueryFactory::createKeywordSearchQuery($params);

        
        if ($sort != null){
            $str_split = explode('.', $sort);
            $sortField = $str_split[0];
            $isAscending= ($str_split[1]=='a') ? true:false; 
           
            $searchSort = new SearchSort($sortField, $isAscending);
            $searchQuery->addSort($searchSort);
            
        }
        $searchQuery->setMaxItems(10);//temporary to display all results until scroll to load more is implemented.
        $searchQuery->setStartIndex($offset);
        $searchQuery->addReturnField("title");
        $searchQuery->addReturnField("id");
        $searchQuery->addReturnField("hasid");
        $searchQuery->addReturnField("hasdescription");
        $searchQuery->addReturnField("tags");
        $searchQuery->addReturnField("lastupdate");
        $searchQuery->addReturnField("isCatalogue");
        $searchQuery->addReturnField("href");
        $searchQuery->addReturnField("parentUrl");
        $searchQuery->addReturnField("datahub");
        $searchQuery->addReturnField("name");
        $searchQuery->addReturnField("iscontenttype");
        $searchQuery->addReturnField("maintainer");
        $data = $controller->query($searchQuery, 'AND');

        if (!isset($data["response"])) {
          throw new KurogoDataException("Error, not a valid response.");
        }

        return json_encode ($data["response"]);
    }   


}