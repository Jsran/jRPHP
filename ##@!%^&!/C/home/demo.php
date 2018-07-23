<?php
namespace jR\C\home;
use jR\M;
use jR\I;
class demo extends Base
{
	public $layout = null;

	public function index()
	{
		# 清理代码缓存
		parent::opcache();
		dump(parent::$args);
		dump("我是home模块的 demo 控制器中的 index动作!");
		# 父层调用
		parent::jump();
		# 同类调用
		self::demos();
		# 实例化一个M根类
		$ob = new M\User;
		dump(
			$ob->
			# 排除指定字段
			select('count(1) c,sum(id) e',true)->
			having(['c >= :having_c',':having_c' => 1])->
			where(['id' => 10000])->
			run()
		);
		#dump($ob->OneSql("select name from s_config "));
		#
		# 实例化一个M模型exit
		# 事务执行
		dump($ob->action(function($M){
			# dump($M->oneSql("select * from s_user where id = :id",[':id' => 10000]));
			return ['我是事务中的返回'];
		}));
		# 联合查询
		dump(
			$ob->
			table('s_user a')->
			select('!id,user,total,no_use_money,use_money,shou_money',true)->
			leftjoin('s_user_money',['a.id = uid'])->
			where(['a.id'=> 10000])->
			run(true)
		);
		# 更新一个数据
		dump(
			$ob->
			table('s_user')->
			update(['pass' => md5('123456'),'link = link + 1'])->
			where(['id > :id','user = :user or real_name = :xx',':id'=>10001,':user'=>'hehe',':xx' => '呵呵哒'])->
			run(true)
		);
		# 插入或更新
		dump(
			$ob->
			insert(['user' => 'jsran', 'pass' => md5('123456')])->
			duplicate(['link = link + 1','pass = values(pass)','login_ip' => '1.1.1.1'])->
			run(true)
		);
		# 查询单条数据
		dump(
			$ob->
			# 排除指定字段
			select('id,user,pass,phone',true)->
			where(['id' => 10000])->
			run()
		);
		# 查询多条数据
		dump(
			$ob->
			select('id,user,pass,phone')->
			where(['id' => 10000])->
			run()
		);

		# 生产一个URL地址
		dump(url(['m' => 'home', 's' =>'demo' , 'i' => 'index' , 'index'=> '']));
		# 插件静态化测试
		// I\JScUrl::open('https://www.baicaif.com','get');
		// if(I\JScUrl::send())
		// { # 打印服务器返回的信息
		// 	dump(I\JScUrl::reqHead());
		// 	dump(I\JScUrl::resHead());
		// 	# dump(I\JScUrl::retText());
		// }else
		// { # 打印错误信息
		// 	dump(I\JScUrl::error());
		// }
		# 插件实例化测试
		// $Formula = new I\InterestFormula;
		// dump( 
		//   $Formula->
		// 	Money(10000)->
		// 	# 利率 8%
		// 	Rate(0.06)->
		// 	# 期数
		// 	Period(3)->
		// 	# 回息公式
		// 	Formula(5)->
		// 	# 利息管理 1.5%
		// 	Manage(0.015)->
		// 	# 平台加息 1.7%
		// 	AIRate(0.020)->
		// 	# 额外加息 0.5%
		// 	EIRate(0.005)->
		// 	# 计息时间
		// 	InterestDate(1532059140)->
		// 	Run()
		// );
		// $ob = new I\JCbank(['18399999999']);
		// $ob::$Fuid = ['18399999999','18305555555'];
		// dump($ob->projectQuery(['bid' => '143714']));
		
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