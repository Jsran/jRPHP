<?php
namespace jR;
class jR
{
	private $class = [];
	private $modsi = ['home','demo','index'];
	public function __construct()
	{ # 构造函数
		$this->act = microtime(true);
      	#echo "<pre>\r\n";
		#echo "Code start\r\n";
	}
	public function __destruct()
	{ # 析构函数
		if(!defined('ERR')):
		#dump('Runing Times:' . round(microtime(true)-$this->act,8) . ' Sec / Memory: ' . round(memory_get_usage()/1024,8) . ' Kb');
		#dump('Code end');
		endif;
    }
	private function roule()
	{ # 处理路由
		
		if(!CLI && WEB) GOTO WEB;
		$this->setDefine([ 'MOBILE' => 0, 'HOST' => 'php' ]);
		$param = $_SERVER['argv'];
		array_shift($param);
		$args = [];
		if(!empty($param))
		{ # 有参数
			array_walk($param, function($k)use(&$args){
				$v = explode('=',$k);
				$args[$v[0]] = $v[1];
			});
		}
		GOTO ROULE;
		WEB:
		$this->setDefine(['SCHEME' => (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == "https") || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? 'https://' : 'http://']);
		$this->setDefine(['HOST' => SCHEME.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/\\').'/', 'MOBILE' => isMobile()]);
		if(empty($GLOBALS['rewrite'])) GOTO INPUT;
		foreach($GLOBALS['rewrite'] as $rule => $mapper):
			if('/' == $rule)$rule = '/$';
			if(0!==stripos($rule, SCHEME))
				$rule = SCHEME.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/\\') .'/'.$rule;
			$rule = '/'.str_ireplace(array('\\\\', SCHEME, '/', '<', '>',  '.'), 
				array('', '', '\/', '(?P<', '>[-\w]+)', '\.'), $rule).'/i';
			if(preg_match($rule, SCHEME.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], $matchs)):
				$route = explode("/", $mapper);
				if(isset($route[2])):
					list($_GET['m'], $_GET['s'], $_GET['i']) = $route;
				else:
					list($_GET['s'], $_GET['i']) = $route;
				endif;
				foreach($matchs as $matchkey => $matchval):
					if(!is_int($matchkey))$_GET[$matchkey] = $matchval;
				endforeach;
				break;
			endif;
		endforeach;
		INPUT:
		parse_str($_SERVER['REDIRECT_QUERY_STRING'] ?? $_SERVER['QUERY_STRING'] ?? null, $args);
       	parse_str(file_get_contents('php://input'), $input);
      	$args = array_merge($_POST,$_COOKIE,$input,$args,$_GET);
		ROULE:
		$this->setDefine([
			'M'=>$args['m']??$this->modsi[0],
			'S'=>$args['s']??$this->modsi[1],
			'I'=>$args['i']??$this->modsi[2],
		]);
		self::runing(M,S,I,$args);
	}
	private function runing($module,$controller,$action,$args)
	{ # 模块 控制器 动作 检测过滤并执行
		$__ = __NAMESPACE__."\C\\$module\\$controller";
		if(!is_available_classname($module)) _err_router("Err: Module '$module' is not correct!");
		if(!is_dir(PATH.DS.CORE.DS.'C'.DS.$module)) _err_router("Err: Modoule '$module' is not exists!");
		if(!is_available_classname($controller)) _err_router("Err: Controller '$controller' is not correct!");
		if(!file_exists(PATH.DS.CORE.DS.'C'.DS.$module.DS.$controller.'.php')) _err_router("Err: Controller '$controller' of '$module'  is not exists!");
		$ob = new $__;
		if(!method_exists($ob, $action)) _err_router("Err: Method '$action' of '$module' -> '$controller' is not exists!");
		$ob->display_file = $module.'/'.args($GLOBALS['site']['theme'][$module],'Default','s').
			(in_array($module,$GLOBALS['model']['Mobile'])?('/'.(MOBILE?'Mobile':'Computer')):null);
		setContentReplace(['__THEMES__' => HOST . ( CLI ?  NULL :"style/".styleget($ob->display_file)), '__HOST__' => HOST ]);
		$ob::$args = I\Filter::val($args);
		$ob->$action();
		if($ob->_auto_display):
			$tpl_name = $controller.$GLOBALS['view']['sep'].$action.$GLOBALS['view']['suffix'];
			$auto_tpl_name = $ob->display_file.DS.$tpl_name;
			if(file_exists($GLOBALS['view']['theme'].DS.$auto_tpl_name))$ob->display($tpl_name);
		endif;
	}
	public function setDefine($arr = [])
	{ # 设置
		array_walk($arr, function($v,$k){defined($k) or define($k,$v);});
	}
	public function setHook()
	{ # 设置

	}
	public function autoload($class)
	{ # 自动加载
		if(isset($this->class[$class])) return;
		$class =preg_replace(sprintf("/^%s\\\(C|M|V)$/",PACK),PACK.'\S\\\$1',$class,1);
		$new = preg_replace(sprintf("/^%s/",PACK),CORE,$class,1);
	    $new = explode('\\', $new);
	    $file = PATH.DS.implode(DS, $new).'.php';
	    if(is_file($file) && include $file) $this->class[$class] = true;
	}
	public function Run()
	{ # 运行
		# 运行模式
		$this->setDefine(['CLI' => PHP_SAPI === 'cli','WEB' => strpos(PHP_SAPI,'apache') !== false || strpos(PHP_SAPI,'cgi') !== false]);
		# 配置文件
		$GLOBALS += require PATH. DS . CORE. DS. 'config.php';
		# 公共方法
        include PATH. DS . CORE. DS. 'function.php';
		# 自动加载
		spl_autoload_register([$this,'autoload']);
		# 异常捕获
		WEB ? set_error_handler("_err_handle") ||  set_exception_handler('_exc_handler') || register_shutdown_function("_fal_handler"): NULL;
		# 永不超时
		set_time_limit(0);
		ini_set("magic_quotes_runtime",0);
		if($GLOBALS['debug']):
			error_reporting(-1);
			ini_set("display_errors", "On");
		else:
			error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
			ini_set("display_errors", "Off");
			ini_set("log_errors", "On");
			ini_set("error_log", PATH.DS."O".DS."error_log".DS."phplog_".date('Ymd').".log");
		endif;
		self::roule();
	}
}