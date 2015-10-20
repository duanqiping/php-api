<?php
/**
 * Created by PhpStorm.
 * User: qiping
 * Date: 2015/7/26 0026
 * Time: 22:50
 */
    require_once 'Help.class.php';

    //处理用户的添加/更新/删除……
    $oper = $_REQUEST['oper'];
    if($oper == 'add')
    {
        $title = $_POST['title'];
        $content = $_POST['content'];

        $help = new Help();
        $sql = "insert into news VALUES (null,'$title','$content',null)";
        if( $help->execute_dql($sql))
        {
            //生成静态文件
            //$id=mysql_insert_id();
            $id=$help -> getId();
            $html_filename='news-id'.$id.'.html';
            //取出当前的年月日创建一个文件夹，把这个静态页面放入这个文件夹中。（防止文件过多，以后搜索时间过长）
            $html_fp=fopen('../'.$html_filename,'w');
            //把模板文件读取
            $fp=fopen('news.tpl','r');

            //循环读取
            //如果没有读到文件的最后，就一直读取
            while(!feof($fp))
            {
                //一行行读
                $row = fgets($fp);
                //把占位符替换掉 -> 小函数myreplace
                $row = str_replace('%title%',$title,$row);
                $row = str_replace('%content%',$content,$row);

                fwrite($html_fp,$row);
            }
            //关闭文件
            fclose($html_fp);
            fclose($fp);
            echo "恭喜你，添加成功<a href='manage.html'>管理界面</a>";
            //怎样让首页立即更新
            include "newsList.php";
        }
        else
        {
            die('添加失败');
        }
    }

    if($oper == 'clickUpdate')
    {
        $id = $_GET['id'];

        $help = new Help();
        $sql = "select * from news WHERE id='$id'";
        $res = $help -> execute_sql($sql);
        $title = $res[0]['title'];
        $content = $res[0]['content'];
        header("location: updateNews.php?title=$title&content=$content&id=$id");
    }

    if($oper == 'update')
    {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $id = $_POST['id'];

        $help = new Help();
        $sql = "update news set title='$title', content='$content' WHERE id='$id'";
        if($help->execute_dql($sql))
        {
            //echo "更新成功!";
            header("location: newsList.php");
        }
        else
        {
            echo "更新失败!";
        }
    }