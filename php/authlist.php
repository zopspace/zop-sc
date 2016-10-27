<?php
require_once "csvloader.php";

function getauthors(){
    $csv = $GLOBALS["csv"];
    //print_r($csv);
    $authors = array();
    foreach ($csv as $vid){
       $owner = $vid["Owner"];
       if ( !in_array($owner, $authors) ) {
          $authors [] = $owner;
          //print $vid['Title'].":".$vid["Owner"]."\n";
       }
    }
    
    return json_encode($authors, JSON_UNESCAPED_UNICODE);
}

echo getauthors();
?>