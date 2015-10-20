<?php
/**
 * Created by PhpStorm.
 * User: qiping
 * Date: 2015/8/23 0023
 * Time: 9:08
 */
$str = 'fdjsiof齐平fdio9080段齐平8909';
//需求 查出有多少个汉字，多少个英文字母，多少个数字
//汉字的 utf-8编码范围 \x4e00-\x9fa5

$reg1 = '/[\x{4e00}-\x{9fa5}]/iu'; // u表示按 模式字符串被认为是utf-8的
preg_match_all($reg1,$str,$res1);

echo "汉字的个数有".count($res1[0])."个";

echo "<pre>";
print_r($res1);
echo "</pre>";

$reg2 = '/[a-zA-Z]/i'; // u表示按 模式字符串被认为是utf-8的
preg_match_all($reg2,$str,$res2);

echo "字母的个数有".count($res2[0])."个";

echo "<pre>";
print_r($res2);
echo "</pre>";

$reg3 = '/[0-9]/i'; // u表示按 模式字符串被认为是utf-8的
preg_match_all($reg3,$str,$res3);

echo "数字的个数有".count($res3[0])."个";

echo "<pre>";
print_r($res3);
echo "</pre>";