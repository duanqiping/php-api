<?php
/*
多文件上传类
*/

/*
上传文件
配置允许的后缀
配置允许的大小
随机生成目录
随机生成文件名

获取文件后缀
判断文件的后缀.

良好的报错的支持

*/
defined('ACC')||exit('Acc Denied');
class UpTool {
    protected $allowExt = 'jpg,jpeg,gif,png,txt,xlsx,xls,doc,docx,pdf,dwg';
    protected $maxSize = 10; //1M,M为单位
 

    protected $errno = 0; // 错误代码
    protected $upfiles = array();
    protected $error = array(
        0=>'无错',
        1=>'上传文件超出系统限制',
        2=>'上传文件大小超出网页表单页面',
        3=>'文件只有部分被上传',
        4=>'没有文件被上传',
        6=>'找不到临时文件夹',
        7=>'文件写入失败',
        8=>'不允许的文件后缀',
        9=>'文件大小超出的类的允许范围',
        10=>'创建目录失败',
        11=>'移动失败'
            
    );
    protected $icon_url = array(
        'jpg'=>'pic',
        'jpeg'=>'pic',
        'png'=>'pic',
        'gif'=>'pic',
        'txt'=>'txt',
        'xlsx'=>'xls',
        'xls'=>'xls',
        'doc'=>'doc',
        'docx'=>'doc',
        'pdf'=>'pdf',
        'ppt'=>'ppt',
        'pptx'=>'ppt',
        'zip'=>'zip',
        'rar'=>'zip',
        'dwg'=>'dwg'

        );
    protected $type = array(
        'jpg'=>'pic',
        'jpeg'=>'pic',
        'png'=>'pic',
        'gif'=>'pic',
        'txt'=>'file',
        'xlsx'=>'file',
        'xls'=>'file',
        'doc'=>'file',
        'docx'=>'file',
        'pdf'=>'file',
        'dwg'=>'file'
        );

    
    
    public function up($key) {

        $ups = multiple($_FILES);
        
        $ups = $ups[$key];

        foreach($ups as $k=>$f){
            // 检验上传有没有成功
            
            if($f['error']) {

                $this->errno = $f['error'];
                continue;
            }

             
            // 获取后缀
            $ext = $this->getExt($f['name']);

            
            // 检查后缀
            if(!$this->isAllowExt($ext)) {
                
                $this->errno = 8;
                return false;
            }
            
            // 检查大小
            if(!$this->isAllowSize($f['size'])) {
                $this->errno = 9;
                return false;
            }

            // 通过

            //创建目录
            $dir = $this->mk_dir();
            
            if($dir == false) {

                $this->error = 10;
                return false;
            }
       
            // 生成随机文件名
            $newname = $this->randName() . '.' . $ext;


            $dir = $dir . '/' . $newname;
            

            // 移动

            if(!move_uploaded_file($f['tmp_name'],$dir)) {

                $this->errno = 11;
                return false;
            }


            $info['file_url'] = str_replace(MROOT.'Guest/','',$dir);


            $info['type'] = $this->getftype($ext);
            $info['title'] = $f['name'];
            $info['icon_url'] = $this->geticonurl($ext);
            $this->fpaths[] = $info;


            }


        return $this->fpaths;
    }

    public function getErr() {
        return $this->error[$this->errno];
    }
    public function geticonurl($k) {
        return $this->icon_url[$k];
    }
        public function getftype($k) {
        return $this->type[$k];
    }

    /*
        parm string $exts 允许的后缀
    */
    public function setExt($exts) {
        $this->allowExt = $exts;
    }

    public function setSize($num) {
        $this->maxSize = $num;
    }

    /*
        parm String $file
        return String $ext 后缀
    */
    public function getExt($file) {
        $tmp = explode('.',$file);
        return end($tmp);
    }

    /*
        parm String $ext 文件后缀
        return bool

        防止大小写的问题 JPG
    */
    protected function isAllowExt($ext) {
        return in_array(strtolower($ext),explode(',',strtolower($this->allowExt)));
    }


    // 检查文件的大小 
    protected function isAllowSize($size) {
        return $size <= $this->maxSize * 1024 * 1024;
    }


    /*
        按日期创建目录的方法
    */
    protected function mk_dir() {
        
        $dir = MROOT . 'Guest/upload/MOB';
        
        if(is_dir($dir) || mkdir($dir,0777,true)) {
            return $dir;
        } else {
            return false;
        }
    }


    /*
        生成随机文件名
    */

    protected function randName($length = 6) {
        $str = 'abcdefghijkmnpqrstuvwxyz23456789';
        return substr(str_shuffle($str),0,$length);
    }


}


