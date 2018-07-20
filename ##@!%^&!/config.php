<?php
$config = [
	'tags' => [
		'home' => [
			'main' => [
				'index' =>  [ 
					'main_demo' => ['ActivityH','main_demo'],
				],
			],
		],
	],
	'site' => [
		'theme' => 
			['home' => 'Default'],
	],
	'urllog' => [ # url 请求日志
		'switch' => true, # 是否开启请求日志记录
		'file'	 => ROOT_PATH.'O'.DS.'url_log',
	],
	'rewrite' => [ # Url重写配置
		'home.do'			=> 'main/index',
		'<s>/<i>.do'		=> '<s>/<i>',
	],
	'model' => [
		'm'	=> 'home',
		's'	=> 'main',
		'i'	=> 'index',
		'Mobile'	=> ['home'], # 启用 模块下 PC Mobile双支持
		'bindParam'	=> [
			'home/main/index' => 'home/main/hindex', # 配合Mobile使用 手机 电脑大部分功能都一样 个别功能需要单独实现
			],
	],
	'view' => [ # 视图模板配置
		'image'	=> "Entry/not.jpg",# 图片不存在时默认显示
		'cache' => PATH.DS.'O'.DS.'cache', # 缓存
		'theme'	=> PATH.DS."V",  # 模板
		'left'	=> '{', # 左边定界符
		'right'	=> '}', # 右边定界符
		'sep'	=> "/", # 自动输出链接符
		'suffix'=> '.html', # 模板后缀
		'contentReplace' => [], # 模板内容替换器
		'style' => [
			'home/Default/Computer'	=> 'HOMEPC',
			'home/Default/Mobile'	=> 'HOMEH5',
		],
	],
	'rights'=> [ # 权限管理
		'system'=> [ # model
			'auto'	=> true, # 权限开关
			'jump'	=> 'rights', # 无权限指向函数 model 对应的 Base 类中
		],
		'home'	=> [
			'auto'	=> false,
			'jump'	=> 'rights',
		],
	],
	'debug' => true,
	'mysql' => [
		'HOST' => '101.200.139.212',
		'PORT' => '3306',
		'USER' => 'demos',
		'DB'   => 'baicai',
		'PASS' => 'mimabudui',
		'CHARSET' => 'utf8',
		'SLAVE' => [
			[
				'HOST' => '101.200.139.212',
				'PORT' => '3306',
				'USER' => 'demos',
				'DB'   => 'baicai',
				'PASS' => 'mimabudui',
				'CHARSET' => 'utf8',
			],
			[
				'HOST' => '101.200.139.212',
				'PORT' => '3306',
				'USER' => 'demos',
				'DB'   => 'baicai',
				'PASS' => 'mimabudui',
				'CHARSET' => 'utf8',
			],
		],
	], 
];

return $config;