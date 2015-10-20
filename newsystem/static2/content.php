<?php
/**
 * Created by PhpStorm.
 * User: qiping
 * Date: 2015/8/9 0009
 * Time: 21:33
 */
echo "<pre>";

//print_r($_SERVER);
//echo "</pre>";
$path_info = $_SERVER['PATH_INFO'];
//echo $path_info;

$preg = '/(\d+).(\d+).(\d+).html/i';

preg_match($preg,$path_info,$res);

print_r($res);
exit();