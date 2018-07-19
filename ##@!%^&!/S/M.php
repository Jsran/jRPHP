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
	public function findAll($conditions = array(), $sort = null, $fields = '*', $limit = null)
	{ # 查找全部
		$sort = !empty($sort) ? ' order by '.$sort : '';
		$conditions = $this->_where($conditions);

		$sql = ' from '.$this->table_name.$conditions["_where"];
		if(is_array($limit)){
			$total = $this->query('select count(*) as c '.$sql, $conditions["_bindParams"]);
			if(!isset($total[0]['c']) || $total[0]['c'] == 0)return array();
			
			$limit = $limit + array(1, 10, 10);
			$limit = $this->pager($limit[0], $limit[1], $limit[2], $total[0]['c']);
			$limit = empty($limit) ? '' : ' LIMIT '.$limit['offset'].','.$limit['limit'];			
		}else{
			$limit = !empty($limit) ? ' LIMIT '.$limit : '';
		}
		return $this->query('select '. $fields . $sql . $sort . $limit, $conditions["_bindParams"]);
	}
	
	public function find($conditions = array(), $sort = null, $fields = '*')
	{ # 查找一条
		$res = $this->findAll($conditions, $sort, $fields, 1);
		return !empty($res) ? array_pop($res) : false;
	}
	
	public function update($conditions, $row)
	{ # 更新数据
		$values = array();
		foreach ($row as $k=>$v){
			$values[":M_UPDATE_".$k] = $v;
			$setstr[] = "`{$k}` = ".":M_UPDATE_".$k;
		}
		$conditions = $this->_where( $conditions );
		return $this->execute("update ".$this->table_name." set ".implode(', ', $setstr).$conditions["_where"], $conditions["_bindParams"] + $values);
	}
	public function delete($conditions)
	{ # 删除数据
		$conditions = $this->_where( $conditions );
		return $this->execute("delete from ".$this->table_name.$conditions["_where"], $conditions["_bindParams"]);
	}
	public function create($row)
	{ # 创建数据
		$values = array();
		foreach($row as $k=>$v){
			$keys[] = "`{$k}`"; $values[":".$k] = $v; $marks[] = ":".$k;
		}
		$this->execute("insert into ".$this->table_name." (".implode(', ', $keys).") values (".implode(', ', $marks).")", $values);
		return $this->dbInstance($GLOBALS['mysql'], 'master')->lastInsertId();
	}
	public function findCount($conditions)
	{ # 获取总条数
		$conditions = $this->_where( $conditions );
		$count = $this->query("select count(*) c from ".$this->table_name.$conditions["_where"], $conditions["_bindParams"]);
		return isset($count[0]['c']) && $count[0]['c'] ? $count[0]['c'] : 0;
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
		if($readonly && !empty($GLOBALS['mysql']['SLAVE'])){
			$slave_key = array_rand($GLOBALS['mysql']['SLAVE']);
			$sth = $this->dbInstance($GLOBALS['mysql']['SLAVE'][$slave_key], 'slave_'.$slave_key)->prepare($sql);
		}else{
			$sth = $this->dbInstance($GLOBALS['mysql'], 'master')->prepare($sql);
		}
		if(is_array($params) && !empty($params)){
			foreach($params as $k => &$v){
				if(is_int($v)){
					$data_type = PDO::PARAM_INT;
				}elseif(is_bool($v)){
					$data_type = PDO::PARAM_BOOL;
				}elseif(is_null($v)){
					$data_type = PDO::PARAM_NULL;
				}else{
					$data_type = PDO::PARAM_STR;
				}
				$sth->bindParam($k, $v, $data_type);
			}
		}
		if($sth->execute())return $readonly ? $sth->fetchAll(PDO::FETCH_ASSOC) : $sth->rowCount();
		$err = $sth->errorInfo();
		err('Database SQL: "' . $sql. '", ErrorInfo: '. $err[2], 1);
	}

	public function dbInstance($db_config, $db_config_key, $force_replace = false)
	{ # 切换主数据库
		if($force_replace || empty($GLOBALS['instances'][$db_config_key])){
			try {
				$GLOBALS['instances'][$db_config_key] = new PDO('mysql:dbname='.$db_config['DB'].';host='.$db_config['HOST'].';port='.$db_config['PORT'], $db_config['USER'], $db_config['PASS'], array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES \''.$db_config['CHARSET'].'\''));
			}catch(PDOException $e){err('Database Err: '.$e->getMessage());}
		}
		return $GLOBALS['instances'][$db_config_key];
	}
	private function _where($conditions){
		$result = array( "_where" => " ","_bindParams" => array());
		if(is_array($conditions) && !empty($conditions)){
			$fieldss = array(); $sql = null; $join = array();
			if(isset($conditions[0]) && $sql = $conditions[0]) unset($conditions[0]);
			foreach( $conditions as $key => $condition ){
				if(substr($key, 0, 1) != ":"){
					unset($conditions[$key]);
					$conditions[":".$key] = $condition;
				}
				$join[] = "`{$key}` = :{$key}";
			}
			if(!$sql) $sql = join(" and ",$join);

			$result["_where"] = " where ". $sql;
			$result["_bindParams"] = $conditions;
		}
		return $result;
	}
}