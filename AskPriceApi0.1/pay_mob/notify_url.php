<?php
/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。


 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */

require_once("alipay.config.php");
require_once("lib/alipay_notify.class.php");

//计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyNotify();

if($verify_result) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代

	
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
	//商户订单号

	$out_trade_no = $_POST['out_trade_no'];

	//支付宝交易号

	$trade_no = $_POST['trade_no'];

	//交易状态
	$trade_status = $_POST['trade_status'];
//买家支付宝账号
   $buyer_email = $_POST['$buyer_email'];
   
   //交易总金额
   $total_fee = $_POST['total_fee'];

    if($_POST['trade_status'] == 'TRADE_FINISHED') {
		//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//如果有做过处理，不执行商户的业务程序
				
		//注意：
		//该种交易状态只在两种情况下出现
		//1、开通了普通即时到账，买家付款成功后。
		//2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。

        //调试用，写文本函数记录程序运行情况是否正常
        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
    }
    else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
		//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//如果有做过处理，不执行商户的业务程序
				
		//注意：
		//该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。

        //调试用，写文本函数记录程序运行情况是否正常
        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
   
		$conn =  mysql_connect('localhost', 'root','aecsqlyou');
		if(!$conn){
                return false;
		}
	    $sql = 'set names utf8';
	    mysql_query($sql);
	    $sql = 'use ecshop';
	    mysql_query($sql);
    //判断此订单状态是否为1；
      $sql = 'select state from ecs_temp_purchase where temp_purchase_sn = \''.$out_trade_no.'\'';
      $rs = mysql_query($sql);
	    if(!$rs){
	  
	    	    return false;
	    }
     $row = mysql_fetch_row($rs);
	    if($row[0] != 1 ){
             return false;
    
	    }

	    //修改订单状态为支付成功2
	    $sql = 'update ecs_temp_purchase set state = 2 where temp_purchase_sn = \''.$out_trade_no.'\'';

	    if(!mysql_query($sql)){
	
	    	    return false;
	    }

	    if(mysql_affected_rows()<=0){
             return false;
    
	    }

	    //查订单信息
	    $sql = 'select ecs_temp_purchase.money,ecs_temp_purchase.suppliers_id,ecs_temp_purchase.buyers_id,ecs_temp_buyers.temp_buyers_mobile from ecs_temp_purchase left join ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_purchase.buyers_id where ecs_temp_purchase.temp_purchase_sn = \''.$out_trade_no.'\'';
   		$rs = mysql_query($sql,$conn);
     if(!$rs){  
           return false;
        } 
     $firstRow = mysql_fetch_assoc($rs);
     if(!$firstRow){
          return false;
     }
    //判断卖家有没有账户
         $sql = 'select count(*) from ecs_temp_account where temp_buyers_id ='.$firstRow['suppliers_id'];
         $rs = mysql_query($sql);
         if(!$rs){  
           return false;
        }
         $row = mysql_fetch_row($rs);
         if($row[0] <= 0 ){//没有账户
			         $sqlaccount = 'INSERT INTO ecs_temp_account (temp_buyers_id,total,withdraw) VALUES('.$firstRow['suppliers_id'].',0.00,0.00)';
			        	$res = mysql_query($sqlaccount); 
			        	if(!$res){  
               return false;
        				}
			      			if(mysql_insert_id() <= 0){
			            return false;
			        	}
   
         }

        //查此订单有没有在payment数据库插入过数据
        $sql = 'select count(*) from ecs_temp_payment where temp_purchase_sn =\''.$out_trade_no.'\'';
        $rs = mysql_query($sql);
        if(!$rs){  
               return false;
        				}
        $row = mysql_fetch_row($rs);
        if($row[0] >0 ){
              return false;
         }
        //在payment加上此收入，同时在count里加入缓存 在acount插入一条数据
						mysql_query("START TRANSACTION");
       
    		$sql = 'insert into ecs_temp_payment (temp_purchase_sn,time,from_user,to_user,from_account,to_account,method,type,user_id,money,client_from) values (\''.$out_trade_no.'\',\''.time().'\',\''.$firstRow['temp_buyers_mobile'].'\',\'品材网支付\',\''.$buyer_email.'\',\'hbz@pcw268.com\',0,0,\''.$firstRow['suppliers_id'].'\',\''.$total_fee.'\',1)';

      $sql2 = 'update ecs_temp_account set withdraw = withdraw + '.$total_fee.' where temp_buyers_id ='.$firstRow['suppliers_id'];
 
        
          $res = mysql_query($sql);
         $rc = mysql_insert_id();

         $res1 = mysql_query($sql2); 
         $rc1 = mysql_affected_rows();
         if($rc && $rc1 ){
            mysql_query("COMMIT");

         }else{
             mysql_query("ROLLBACK");

         }
        mysql_query("END");      
       //发短信通知卖家发货
        
        include('../../phpTools/Helper/Shotmessage_helper.php');
        Sendmessage("买家支付成功订单（".$out_trade_no."），请尽快发货给买家。",$firstRow['suppliers_id']);


    }

	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
        
	echo "success";		//请不要修改或删除
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $d = $out_trade_no.','.$trade_no.','.$trade_status.','.$buyer_email;
    logResult('success:'.$d);
}
else {
    //验证失败
    echo "fail";

    //调试用，写文本函数记录程序运行情况是否正常
    $d = $out_trade_no.','.$trade_no.','.$trade_status;
    logResult($d);
}
$d = $out_trade_no.'~'.$trade_no.'~'.$trade_status;
logResult($d);
?>