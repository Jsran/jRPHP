<?php
namespace jR\I;
/**
 * 缓存管理类
 */

class Cache {

    /**
     * 操作句柄
     * @var string
     * @access protected
     */
    protected $handler    ;

    /**
     * 缓存连接参数
     * @var integer
     * @access protected
     */
    protected $options = array();

    /**
     * 连接缓存
     * @access public
     * @param string $type 缓存类型
     * @param array $options  配置数组
     * @return object
     */
    public function connect() {
        $options = $GLOBALS['cache'];
        $storage = $options['storage'] ? $options['storage'] : 'File';
        $class  =   strpos($storage,'\\')? $storage : 'jR\\I\\Cache\\'.ucwords(strtolower($storage));            

        if(class_exists($class))
            $cache = new $class($options);
        else
            err('无法加载该类库:'.$storage);
        return $cache;
    }

    /**
     * 取得缓存类实例
     * @static
     * @access public
     * @return mixed
     */
    static function getInstance($type='',$options=array()) {
		static $_instance	=	array();
        //相同配置 不在实例
		$guid	=	$type.self::to_guid_string($options);
		if(!isset($_instance[$guid])){
			$obj	=	new Cache();
			$_instance[$guid]	=	$obj->connect($type,$options);
		}
		return $_instance[$guid];
    }

    static public function to_guid_string($mix) {
	    if (is_object($mix)) {
	        return spl_object_hash($mix);
	    } elseif (is_resource($mix)) {
	        $mix = get_resource_type($mix) . strval($mix);
	    } else {
	        $mix = serialize($mix);
	    }
	    return md5($mix);
	}

    public function __get($name) {
        return $this->get($name);
    }

    public function __set($name,$value) {
        return $this->set($name,$value);
    }

    public function __unset($name) {
        $this->rm($name);
    }
    public function setOptions($name,$value) {
        $this->options[$name]   =   $value;
    }

    public function getOptions($name) {
        return $this->options[$name];
    }

    public function __call($method,$args){
        //调用缓存类型自己的方法
        if(method_exists($this->handler, $method)){
           return call_user_func_array(array($this->handler,$method), $args);
        }else{
            err(__CLASS__.'::'.$method.' 该缓存类没有定义你所调用的方法');
            return;
        }
    }
}