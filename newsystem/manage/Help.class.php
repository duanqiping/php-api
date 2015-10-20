<?php

    class Help
    {
        private $mysqli;
        private $host="localhost";
        private $user="root";
        private $password="123";
        private $db="test";

        public function __construct()
        {
            $this->mysqli = new Mysqli($this->host, $this->user, $this->password);

            if (!$this->mysqli)
            {
                die("连接失败".$this->mysqli->errno);
            }
            $this->mysqli -> set_charset("utf8");
            $this->mysqli -> select_db($this->db);
        }

        public function execute_sql($sql)
        {
			
            $arr = array();
            $res = $this->mysqli->query($sql) or die("获取资源失败！".$this->mysqli->errno);//注意：不要直接返回$res, 不然讲结果全部为空
            while($row = mysqli_fetch_assoc($res))
            {
                $arr[]=$row;
            }
			mysqli_free_result($res);
            return $arr;
        }
		//添加、删除、
		public function execute_dql($sql)
		{
			$b = $this->mysqli->query($sql) or die("操作失败!".$this->mysqli->errno);
			if(!$b)
			{
				return "操作失败！";
			}
			else
			{
				if($this->mysqli->affected_rows>0)
				{
					return "操作成功！";
				}
				else
				{
					return "操作失败！";
				}
				
			}
		}
        //获取最新插入值的ID
        public function getId()
        {
            return mysqli_insert_id($this->mysqli);
        }
		//关闭连接
		public function close_connect()
		{
			if(!empty($this->mysqli->close()))
			{
				$this->mysqli->close();
			}
		}
    }

