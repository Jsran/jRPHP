<?php
namespace jR\C\home;
use jR\M;
use jR\I;
use DOMDocument;
use DOMXPAth;
class demo extends Base
{
	public $layout = null;


	public function index()
	{
		# 插件实例化测试
		$Formula = new I\InterestFormula;
		dump( 
		  $Formula->
			Money(10000)->
			# 利率 8%
			Rate(0.06)->
			# 期数
			Period(3)->
			# 回息公式
			Formula(5)->
			# 利息管理 1.5%
			Manage(0.015)->
			# 平台加息 1.7%
			AIRate(0.020)->
			# 额外加息 0.5%
			EIRate(0.005)->
			# 计息时间
			InterestDate(1532059140)->
			Run()
		);
	}
	public function hindex()
	{
		dump('我事手机转悠');
	}
	public function demos()
	{
		dump('I am demos!');
	}
}