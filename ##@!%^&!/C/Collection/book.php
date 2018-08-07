<?php
namespace jR\C\Collection;
use jR\M;
use jR\I;
class book extends Base
{
	public $layout = null;

	# 创建表结构
	# CREATE TABLE `s_category` (
	#  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增编号',
	#  `Title` varchar(30) NOT NULL COMMENT '类名',
	#  `Introduction` text COMMENT '简介',
	#  PRIMARY KEY (`Id`)
	# ) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8 COMMENT='小说分类表'
	# 
	# CREATE TABLE `s_information` (
	#  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增编号',
	#  `Title` varchar(30) NOT NULL COMMENT '书名',
	#  `Author` varchar(30) NOT NULL COMMENT '作者',
	#  `Introduction` text COMMENT '简介',
	#  `Tid` tinyint(1) NOT NULL COMMENT '分类',
	#  `Bfrom` varchar(255) DEFAULT '' COMMENT '来源',
	#  `state` tinyint(1) DEFAULT '0' COMMENT '状态 0 连载中 1 已完结',
	#  PRIMARY KEY (`Id`)
	# ) ENGINE=InnoDB AUTO_INCREMENT=12000 DEFAULT CHARSET=utf8 COMMENT='基本信息表'
	# 
	# CREATE TABLE `s_chapter` (
	#  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增编号',
	#  `Iid` int(11) NOT NULL DEFAULT '0' COMMENT '小说编号',
	#  `Title` varchar(50) NOT NULL COMMENT '节名',
	#  `Content` text COMMENT '章节内容',
	#  PRIMARY KEY (`Id`),
	#  UNIQUE KEY `Id` (`Id`),
	#  KEY `Iid` (`Iid`),
	#  FULLTEXT KEY `ft_finds` (`Title`,`Content`)
	# ) ENGINE=MyISAM AUTO_INCREMENT=120000 DEFAULT CHARSET=utf8 COMMENT='章节信息表'

	public function index()
	{
		$id = args(parent::$args['id'],0,'d');
		$tid = args(parent::$args['tid'],0,'d');
		$ob = new M('s_chapter');
		$text = $ob->table('s_chapter a')->select('a.Iid,a.Title,a.Content,b.Title name',true)->leftjoin('s_information b',['a.Iid = b.Id'])->where(['a.Id' => $id,'a.Iid' => $tid])->run();
		if(!$text) parent::err404('该章节不存在!');
		$this->Book = $text['name'];
		$this->Tid = $text['Iid'];
		$this->Title = $text['Title'];
		$this->Content = $text['Content'];
		if($prev = $ob->select('Id',true)->where(['Id < :Id','Iid' => $tid,':Id' => $id])->order('Id desc')->limit('1')->run()) $this->prev = $prev['Id'];
		if($next = $ob->select('Id',true)->where(['Id > :Id','Iid' => $tid,':Id' => $id])->order('Id asc')->limit('1')->run()) $this->next = $next['Id'];

	}
	public function pages()
	{
		parent::opcache();
		$id = args(parent::$args['id'],0,'d');
		if(isset(parent::$args['page']) && is_numeric(parent::$args['page']))
		{
			$page = max(args(parent::$args['page'],1,'d'),1);
			$order = args(parent::$args['order'],1,'d');
			$ob = new M('s_chapter');
			$limit = (($page - 1 ) * 50) . ', 50';
			$res = $ob->select('Id,Iid,Title')->where(['Iid' => $id])->order('Id '. ($order == 1 ? 'asc' : 'desc'))->limit($limit)->run();
			$lindex = ($page-1) * 5 + 1;
			$html = "<ul class='chapter-info-list' id='ChapterList{$lindex}' data-index='{$lindex}'>\r\n";;
			$i =  0b10;
			array_walk($res, function($v,$k) use(&$page,&$html,&$i,$id) {
				$k++;
				$dindex = ($page-1) * 50 + $k;
				$lindex = ($page-1) * 5 + $i;
				$html .= "<li class='global-cut invoke' data-index='{$dindex}'><a href='".url('Collection/book','index',['tid' => $id,'id' => $v['Id']])."'>{$v['Title']}</a></li>\r\n";
				if($k % 10 == 0)
				{
					$html .= "</ul>\r\n";
					if($i <= 5) $html .= "<ul class='chapter-info-list' id='ChapterList{$lindex}' data-index='{$lindex}'>\r\n";
					$i++;
				}				
			});
			exit($html);
		}else
		{
			$ob = new M('s_chapter a');
			$this->conf = $ob->select('a.Iid,ceil(count(a.Id)/10) Pages,max(a.Id) Id,b.Title,c.Title cType,b.Bfrom,b.Author,b.Introduction',true)->
			rightjoin('s_information b',['a.Iid = b.Id'])->
			leftjoin('s_category c',['b.Tid = c.Id'])->
			where(['a.Iid'=> $id])->
			run();
			if(!$this->conf) parent::err404('该书不存在!');
			$res = $ob->
			table('s_chapter')->
			select('Title',true)->
			where(['Iid' => $id])->
			order('Id desc')->run();
			$this->conf['sBook'] = $res['Title'];
		}
		
	}
	public function yunlaige()
	{
		# 4520  战旗凌霄
		# 20690 太古龙象诀
		# 13911 逆天邪神
		# 支持CLI模式
		parent::opcache();
		$id = args(parent::$args['id'],0,'d');
		if(($len = strlen($id)) >=4 ) $tid = substr($id, 0,$len-3);
		$host = "http://www.yunlaige.com/html/{$tid}/{$id}/";
		I\JScUrl::open($host,'GET');
		if(!I\JScUrl::send()) return I\JScUrl::error();
		$str = iconv('GBK','UTF-8//IGNORE',I\JScUrl::retText());
		if(!I\RegExp::All(['/<td>\s+<a href=\"(\d+.html)\">(.*)<\//',$str],$match)) return dump('Not matching data!');
		# 智能识别章节是否未被采集
		$match = array_unique(array_combine($match[1],$match[2]));
		if(!I\RegExp::One(['/book_name\" content=\"(.*?)\"/',$str],$book)) return dump('Not match book name!');
		$ob = new M('s_information');
		if(!($Iid = $ob->select('Id',true)->where(['Title' => $book[1]])->run()))return dump('The book name not exist!');
		$res = $ob->AllSql('select Title from s_chapter where Iid = :Iid',[':Iid' => $Iid['Id']],\PDO::FETCH_COLUMN);
		$res = array_flip($res?array_diff($match, $res):$match);
		if(!$res) return dump('not new chapter!');
		array_walk($res, function($v,$k) use($host,$Iid,&$ob){
			I\JScUrl::open($host.$v,'GET');
			if(I\JScUrl::send())
			{
				$ret = iconv('GBK','UTF-8//IGNORE',I\JScUrl::retText());
				if(I\RegExp::One(['/ads_yuedu2_txt\(\);<\/script><\/div>([\s\S]*?)<\/div>/',$ret],$match))
				{
					$text = preg_replace('/&#[0-9a-fA-FxX]{2,6};/', '', $match[1]);
					$text = preg_replace('/&nbsp;&nbsp;&nbsp;&nbsp;(.*?)<br \/><br \/>/', '<p>$1</p>', $text);
					$text = preg_replace('/<script>(.*?)<\/script>/', '', $text);
					$text = preg_replace('/【.*?】/', '', $text);
					$text = str_replace('本书首发网站HTTP://WWW.YUNLAIGE.COM，百度直接搜索关键词 云来阁', '', $text);
					$text = str_replace('云来阁小说APP软件已经开发完毕，请大家访问http://m.yunlaige.com网站底部就可下载安装安卓以及苹果的APP', '', $text);
					$text = str_replace('百度搜索关键词雲来閣,阅读本书最快的更新章节,或者直接访问网站', '', $text);
					$text = str_replace('云来阁文学网提供无广告弹窗小说阅读', '', $text);
					$text = str_replace('云来阁文学网', '', $text);
					$text = str_replace('云来阁小说文学网', '', $text);
					$text = str_replace('云来阁', '', $text);
					$text = str_replace('ｗｗｗ.ｙｕｎｌａｉｇｅ．ｎｅｔ', '', $text);
					$text = str_ireplace('www.yunlaige.com', '', $text);
					$text = str_ireplace('Www.YunLaiGe.Net', '', $text);
					$text = str_ireplace('m.yunlaige.com', '', $text);
					$text = str_ireplace('m.yunlaige.net', '', $text);
					$text = str_ireplace('APPappshuzhanggui.net', '', $text);
					$text = str_ireplace('http://', '', $text);
					$text = str_replace('（）', '', $text);
					$text = str_replace('()', '', $text);
					$text = str_replace('@@', '', $text);
					$text = str_replace('【】', '', $text);
					$text = str_replace('&nbsp;', '', $text);
					$text = str_replace('<br />', '', $text);
					$text = trim(str_replace('<p></p>', '', $text));
					dump(
						'Title -> ' . iconv('UTF-8','GBK',$k . ' 入库编号: ') .
						$ob->
						table('s_chapter')->
						insert(['Iid' => $Iid['Id'],'Title' => $k,'Content' => $text])->
						run() 
					);
				}
			}
		});
	}
}