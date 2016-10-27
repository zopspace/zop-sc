<?php

function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return $item;
        }
    }

    return NULL;
}

$metadata_file = "ZoP_SC_Videos.csv";
//$metadata_file = "ZoP_SC_Videos2.csv";
$csv = array_map('str_getcsv', file($metadata_file));
//print_r( $csv);
//get the header names
$keys = array_shift($csv);
//print_r($keys);
//turn index into corresponding column name
foreach ($csv as $i=>$row) {
    $csv[$i] = array_combine($keys, $row);
}
//print_r($csv);
$GLOBALS["csv"] = $csv;
?>