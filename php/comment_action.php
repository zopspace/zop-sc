<?php
//http://www.phpclasses.org/blog/package/7772/post/1-PHP-MySQL-Wrapper-Class.html
require "MySQL_wrapper.class.php";
require "config.php";

$video_id     = isset($_REQUEST['video_id']) ? $_REQUEST['video_id'] : "";

//video_id can not be empty
if ( isset($video_id) && strlen($video_id) > 0) { 
    $params = array('video_id' => $video_id); 
   // print_r($params);
    $dbobj = MySQL_wrapper::getInstance($db_host, $db_user, $db_pass, $db_name); 
    
    //connecting to db 
    if ( !$dbobj->connect() ) {
        echo json_encode(array('status' => "error", 'reason' => 'connection issue. contact administrator.'));
        return;
    }
    
    //select query
    $sql = "SELECT id, user_name, comment, creation_time FROM discussion where video_id='".$video_id."'";
    $result = $dbobj->fetchQueryToArray($sql); 
    //print_r($result);
    $dbobj->close();
    if ( count($result) > 0 ){
        echo json_encode(array('status' => "success", 'process' => TRUE, 'data'=>$result));
    } else {
        echo json_encode(array('status' => "success", 'process' => FALSE));
    }
    
} else {
    echo json_encode(array('status' => 'error', 'reason' => 'No comments for the video found.'));
}

?>