<html>
<head>
    <title>修改页面</title>
    <meta htpp-equiv='content-type' content='text/html' charset='utf-8'/>
</head>
<!--我们在添加新闻时，就同时生成一个对应的新闻页面（比如你设计一个好的一个新闻内容显示模板）-->
<form action="newsAction.php" method="post">
    <table>
        <tr><td>新闻标题</td><td><input type="text" name="title" value="<?php echo $_GET['title']?>"></td></tr>
        <tr><td>新闻内容</td><td><textarea cols="50" rows="10" name="content"><?php echo $_GET['content']?></textarea></td></tr>
        <tr><td><input type="submit" value="修改" /></td><td><input type="reset" value="重新填写"></td></tr>
        <!-隐藏区-->
        <input type="hidden" name="id" value="<?php echo $_GET['id']?>">
        <input type="hidden" name="oper" value="update" />
    </table>
</form>
</html>