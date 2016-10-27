<?php

function redirect($url, $statusCode = 303)
{
   header('Location: ' . $url, true, $statusCode);
   die();
}

//check if the request contains any of the following params
if ( isset($_REQUEST['titlecontains']) ||
     isset($_REQUEST['txtcontains']) ||
     isset($_REQUEST['author']) ||
     isset($_REQUEST['tag_input']) ||
     isset($_REQUEST['videoId'])
   ){
   //do nothing and allow the request
} else {
   //no param was set so redirect the user to home page
   redirect('/sc/');
}
?>