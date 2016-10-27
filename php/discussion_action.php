<?php
require "MySQL_wrapper.class.php";
require "config.php";

$comment_user = isset($_REQUEST['disc_user']) ? $_REQUEST['disc_user'] : "";
$comment_body = isset($_REQUEST['disc_body']) ? $_REQUEST['disc_body'] : "";
$video_id     = isset($_REQUEST['video_id']) ? $_REQUEST['video_id'] : "";

//video_id can not be empty
if ( isset($video_id) && strlen($video_id) ) { 
    $data = array('video_id' => $video_id, 'user_name' => $comment_user, 'comment' => $comment_body); 
    $dbobj = MySQL_wrapper::getInstance($db_host, $db_user, $db_pass, $db_name); 
    //connecting to db 
    $dbobj->connect(); 
    //insert the values 
    $result = $dbobj->arrayToInsert('discussion', $data); 
    
    $dbobj->close();
    if ( $result ){
        echo json_encode(array('status' => "success"));
    } else {
        echo json_encode(array('status' => "error", 'reason' => 'error during inserting values.'));
    }
} else {
    echo json_encode(array('status' => 'error', 'reason' => 'No information of the video found.'));
}

?>