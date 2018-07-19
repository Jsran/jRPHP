<?php
namespace jR\I\Cache;
use jR\I\Cache;
/**
 * 数据库方式缓存驱动
 *    CREATE TABLE pre_cache (
 *      name varchar(255) NOT NULL,
 *      expire int(11) NOT NULL,
 *      data blob,
 *      UNIQUE KEY `name` (`name`)
 *    );
 */
class Db extends Cache {

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options=array()) {
        $this->options  =   $options;   
        $this->handler   = new \jR\M(strtolower($this->options['db']['table']));
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name) {
        $name       =  $this->options['prefix'].addslashes($name);
        $result     = $this->handler->oneSql("select * from ".$this->options['db']['table']." where name='{$name}' and (expire=0 or expire>".time().")");
        if(!empty($result)) {
            $content   =  $result['data'];
            if($this->options['compress'] && function_exists('gzcompress')) {
                //启用数据压缩
                $content   =   gzuncompress($content);
            }
            $content    =   unserialize($content);
            return $content;
        }
        else {
            return false;
        }
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value,$expire=null) {

        $data   =  serialize($value);
        $name   =  $this->options['prefix'].addslashes($name);
        if( $this->options['compress'] && function_exists('gzcompress')) {
            //数据压缩
            $data   =   gzcompress($data,3);
        }
        if(is_null($expire)) {
            $expire  =  $this->options['expire'];
        }
        $expire	    =   ($expire==0)?0: (time()+$expire) ;//缓存有效期为0表示永久缓存
        $result     = $this->handler->oneSql("select * from ".$this->options['db']['table']." where name='{$name}'");
        if(!empty($result) ) {
        	//更新记录
            $result = $this->handler->runSql("update ".$this->options['db']['table']." set data = '{$data}',expire = {$expire} where name = '{$name}'");
        }else {
            $result = $this->handler->runSql("insert into ".$this->options['db']['table']." (name,data,expire) values ('{$name}','{$data}',{$expire})");
        }
        if($result === false) {
            return false;
        }else {
            return true;
        }
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name) {
        $name  =  $this->options['prefix'].addslashes($name);
        return $this->handler->runSql("delete from ".$this->options['db']['table']." where name='{$name}'");
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear() {
        return $this->handler->runSql("delete from ".$this->options['db']['table']);
    }

}