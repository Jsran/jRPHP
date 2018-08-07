<?php
namespace jR\C\Collection;
use jR\C;
class Base extends C
{
	public $layout = "layout.html";

	public function jump()
	{
		echo "我是 base 的jump\r\n";
	}

	public static function err404($msg)
	{
		# header("HTTP/1.0 404 Not Found");
		dump($msg);
		exit;
	}

	public function opcache()
	{
		$call = function_exists('opcache_reset')? 'opcache_reset' : (function_exists('accelerator_reset')? 'accelerator_reset' : null);
		if($call) call_user_func($call);
	}
}