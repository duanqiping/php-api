<?php
	require_once "Help.class.php";
	$help = new Help();
	
	$sql ="select * from news";
	$res = $help->execute_sql($sql);
	//var_dump($res);
	ob_start();
    echo "<meta htpp-equiv='content-type' content='text/html' charset='utf-8'/>";
	echo "<h1>新闻列表</h1>";

	echo "<table>";
	echo "<tr><td>id</td><td>标题</td><td>查看详情</td><td>修改新闻</td></tr>";
	for($i=0; $i<count($res); $i++)
	{
        $id = $res[$i]['id'];
		echo '<tr><td>'.$res[$i]['id'].'</td><td>'.$res[$i]['title'].'</td>
		<td><a href="news-id'.$res[$i]['id'].'.html">查看详情</a></td><td><a href="newsAction.php?oper=clickUpdate&id='."{$id}".'">修改页面</a></td></tr>';
	}
	echo '</table>';
    //这里我们可以把ob内容取出，并生成一个静态页面
    $str_ob=ob_get_contents();
    //把这个$str_ob保存到index.html
    file_put_contents("../index.html", $str_ob);

    //清空缓存
    ob_clean();
    echo "更新首页成功！";
    echo "<a href='../index.html'>点击查看最新首页列表</a>";

?>