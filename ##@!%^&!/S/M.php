<?php
namespace jR;
use PDO;
class M{
	public $page;
	public $table_name;
	
	private $sql = array();
	
	public function __construct($table_name = null){if($table_name)$this->table_name = $table_name;}
	public function oneSql( $sql, $params = array())
	{ # 获取一条数据
		$res = $this->query($sql." LIMIT 1", $params);
		return array_pop($res);
	}
	public function allSql( $sql, $params = array(), & $pager = null)
	{ # 获取全部数据
		if(is_array($pager))
		{ # 是否数组
			$total = $this->query('select count(*) as counter from ('.$sql.') as jsran',$params);
			list($page,$size,$scope) = array_pad($pager,3,10);
			$pager = $this->pager($page,$size,$scope,$total[0]['counter']);
			$limit = empty($pager) ? '' : ' LIMIT '.$pager['offset'].','.$pager['limit'];
		}
		else
		{
			$limit = !empty($pager) ? ' LIMIT '.$pager : '';
		}
		return $this->query($sql."{$limit}", $params);
	}
	public function runSql( $sql, $params = array())
	{ # 执行语句
		return $this->execute($sql,$params,false);
	}
	public function InsertID()
	{
		return $this->dbInstance($GLOBALS['mysql'], 'master')->lastInsertId();
	}
	public function create($row){
		$values = array();
		foreach($row as $k=>$v){
			$keys[] = "`{$k}`"; $values[":".$k] = $v; $marks[] = ":".$k;
		}
		$this->execute("INSERT INTO ".$this->table_name." (".implode(', ', $keys).") VALUES (".implode(', ', $marks).")", $values);
		return $this->dbInstance($GLOBALS['mysql'], 'master')->lastInsertId();
	}
	public function dumpSql(){return $this->sql;}
	public function pager($page, $pageSize = 10, $scope = 10, $total){
		$this->page = null;
		if($total > $pageSize){
			$total_page = ceil($total / $pageSize);
			$page = min(intval(max($page, 1)), $total);
			$this->page = array(
				'total_count' => $total, 
				'page_size'   => $pageSize,
				'total_page'  => $total_page,
				'first_page'  => 1,
				'prev_page'   => ( ( 1 == $page ) ? 1 : ($page - 1) ),
				'next_page'   => ( ( $page == $total_page ) ? $total_page : ($page + 1)),
				'last_page'   => $total_page,
				'current_page'=> $page,
				'all_pages'   => array(),
				'offset'      => ($page - 1) * $pageSize,
				'limit'       => $pageSize,
			);
			$scope = (int)$scope;

			$min = max($page + 2,5);
			$max = min($min,$total_page);
			$act = max($max - 4,1);
			$this->page['all_pages'] = range($act, $max);
			/*
			if($total_page <= $scope ){
				$this->page['all_pages'] = range(1, $total_page);
			}elseif( $page <= $scope/2) {
				$this->page['all_pages'] = range(1, $scope);
			}elseif( $page <= $total_page - $scope/2 ){
				$right = $page + (int)($scope/2);
				$this->page['all_pages'] = range($right-$scope+1, $right);
			}else{
				$this->page['all_pages'] = range($total_page-$scope+1, $total_page);
			}*/
		}
		return $this->page;
	}
	public function query($sql, $params = array()){return $this->execute($sql, $params, true);}
	public function execute($sql, $params = array(), $readonly = false){
		$this->sql[] = $sql;
		if($readonly && !empty($GLOBALS['mysql']['MYSQL_SLAVE'])){
			# 从库查询
			$slave_key = array_rand($GLOBALS['mysql']['MYSQL_SLAVE']);
			$sth = $this->dbInstance($GLOBALS['mysql']['MYSQL_SLAVE'][$slave_key], 'slave_'.$slave_key)->prepare($sql);
		}else{
			$sth = $this->dbInstance($GLOBALS['mysql'], 'master')->prepare($sql);
		}
		if(is_array($params) && !empty($params)){
			foreach($params as $k=>&$v) $sth->bindParam($k, $v);
		}
		if($sth->execute())return $readonly ? $sth->fetchAll(PDO::FETCH_ASSOC) : $sth->rowCount();
		$err = $sth->errorInfo();
		err('Database SQL: "' . $sql. '", ErrorInfo: '. $err[2]);
	}
	public function dbInstance($db_config, $db_config_key, $force_replace = false)
	{ # 切换主数据库
		if($force_replace || empty($GLOBALS['mysql_instances'][$db_config_key])){
			try {
				$GLOBALS['mysql_instances'][$db_config_key] = new PDO('mysql:dbname='.$db_config['MYSQL_DB'].';host='.$db_config['MYSQL_HOST'].';port='.$db_config['MYSQL_PORT'], $db_config['MYSQL_USER'], $db_config['MYSQL_PASS'], array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES \''.$db_config['MYSQL_CHARSET'].'\''));
			}catch(PDOException $e){err('Database Err: '.$e->getMessage());}
		}
		return $GLOBALS['mysql_instances'][$db_config_key];
	}
}