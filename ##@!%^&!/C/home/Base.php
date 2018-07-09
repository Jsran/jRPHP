<?php
namespace jR\C\home;
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
}