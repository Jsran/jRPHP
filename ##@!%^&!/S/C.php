<?php
namespace jR;
use jR\V;
class C{
	public $layout;
	public $_auto_display = true;
	public static $args;
	protected $_v;
	private $_data = array();

	public function init(){}
	public function __construct(){$this->init();}
	public function &__get($name){return $this->_data[$name];}
	public function __set($name, $value){$this->_data[$name] = $value;}

	public function display($tpl_name, $return = false){
		if(!$this->_v) $this->_v = new V($GLOBALS['view']['theme'], $GLOBALS['view']['cache'].DS.$this->display_file,$GLOBALS['view']['left'],$GLOBALS['view']['right'],$this->display_file);
		$this->_v->assign(get_object_vars($this));
		$this->_v->assign($this->_data);
		if($this->layout){
			$this->_v->assign('__template_file', $tpl_name);
			$tpl_name = $this->layout;
		}
		$this->_auto_display = false;
		if($return){
			return $this->_v->render($tpl_name);
		}else{
			echo $this->_v->render($tpl_name);
		}
	}

}