<?php
namespace jR\I;
use PDO;
use Exception;
class M
{
	public $table;
	protected $link = [], $conn, $sql,$exec = ['duplicate','join','where','group','having','order','limit'];

	public function __construct($table = null)
	{ # 构造函数
		if($table)$this->table = $table;
		$this->conn = self::dbInstance();
	}
	public function action($func)
	{ # 事务执行
		if (!is_callable($func)) return false;
		$this->begin();
		try {
			$result = $func($this) ? $this->commit() : $this->rollBack();
		}catch (Exception $e) {
			$this->link['master']->rollBack();
			throw new Exception($e->getMessage());
		}
		return $result;
	}
	public function oneSql($sql,$param = [])
	{ # 单条查询
		self::dbInstance(true);
		# 执行SQL
		return self::execute($sql,$param)->fetch(PDO::FETCH_ASSOC);
	}
	public function allSql($sql,$param = [])
	{ # 多条查询
		self::dbInstance(true);
		# 执行SQL
		return self::execute($sql,$param)->fetchAll(PDO::FETCH_ASSOC);
	}
	public function runSql($sql,$param = [])
	{ # 执行操作
		self::dbInstance();
		# 执行SQL
		return self::execute($sql,$param)->rowCount();
	}
	public function begin()
	{ # 开始事务
		$this->link['master']->beginTransaction();
	}
	public function commit()
	{ # 提交事务
		$this->link['master']->commit();
	}
	public function rollBack()
	{ # 回滚事务
		$this->link['master']->rollBack();
	}
	public function table($table) : self
	{ # 选择表
		$this->table = $table;
		return $this;
	}
	public function update(array $data) : self
	{ # 更新数据
		self::__init(__function__);
		
		$this->sql = "update {$this->table}" . self::wo($data,'set',', ');
		return $this;
	}
	public function insert(array $data) : self
	{ # 新增数据
		self::__init(__function__);
		$this->sql = "{$this->run['first']} into {$this->table} ";
		array_walk($data, function($v,$k) use(&$mark){$mark['k'][] = "`{$k}`";$mark['v'][":i_{$k}"] = $v; $mark['i'][] = ":i_{$k}";});
		$this->run['bind'] += $mark['v'];
		$this->sql .= "(". implode(', ', $mark['k'] ) .") values (" . implode(', ', $mark['i'] ) .")";
		return $this;
	}
	public function delete() : self
	{ # 删除数据
		self::__init(__function__);
		$this->sql = "{$this->run['first']} from {$this->table}";
		return $this;
	}
	public function select($field = '*') : self
	{ # 查询数据
		self::__init(__function__);
		$this->sql = "{$this->run['first']} {$field} from {$this->table}";
		return $this;
	}
	public function duplicate($data) : self
	{ # 重复更新
		$this->run['duplicate'] = ' on duplicate key update ' . self::wo($data,'set',', ');
		return $this;
	}
	public function join($table,$type = 'inner',$on = []) : self
	{ # 数据联合
		$this->run['join'] =  (isset($this->run['join']) ? " {$this->run['join']} {$type} join {$table}" : "{$type} join {$table}") . 
			self::wo($on,'on');
		return $this;
	}
	public function where(array $where) : self
	{ # 条件设置
		$this->run['where'] = self::wo($where);
		return $this;
	}
	public function group(string $group) : self
	{ # 字段分组
		$this->run['group'] = " group by {$group}";
		return $this;
	}
	public function having(string $having) : self
	{ # 聚合过滤
		$this->run['having'] = " having {$having}";
		return $this;
	}
	public function order(string $order) : self
	{ # 字段排序
		$this->run['order'] = " order by {$order}";
		return $this;
	}
	public function limit(string $limit) : self
	{ # 固定条数
		$this->run['limit'] = " limit {$limit}";
		return $this;
	}
	public function run(bool $show = false)
	{ # 执行操作
		array_walk($this->exec, function($v,$k){$this->sql .= $this->run[$v] ?? null;});
		dump($this->run['bind']);
		if($show) return $this->sql;
		self::execute($sql,$this->run['bind']);
	}
	private function __init($first = null)
	{ # 净化变量
		$this->run = null;
		$this->run['bind'] = [];
		$this->run['first'] = is_null($first) ?: $first; 
	}
	private function wo($where,$wo = 'where',$ao = ' and '): string
	{ # 设计条件
		if(is_array($where) && !empty($where))
		{
			$mark = ['join' => [],'where' => []];
			array_walk($where, function($v,$k) use(&$mark,$wo){
				if(gettype($k) == 'integer' ):
					$mark['join'][] = $v;
				elseif(!preg_match('/^:.*+/',$k)):
					$kk =  str_replace('.','' ,$k);
					$mark['join'][] = "`{$k}` = :{$wo}_{$kk}";
					$mark['where'][":{$wo}_{$kk}"] = $v;
				else:
					$mark['where'][$k] = $v;
				endif;
			});
			$this->run['bind'] += $mark['where'];
			return " {$wo} ".join($ao,$mark['join']);
		}
		return null;
	}
	private function pdoTe(&$v)
	{ # 获取类型
		$v = in_array( $t = gettype($v), ['array', 'object']) ? serialize($v) : $v;
		return 
		[ 'integer' => PDO::PARAM_INT, 'boolean' => PDO::PARAM_BOOL, 'NULL' => PDO::PARAM_NULL, 'resource' => PDO::PARAM_LOB ]
		[$v] ?? PDO::PARAM_STR;
	}
	private function execute($sql,$param = [])
	{ # 执行
		# 执行SQL
		$sth = $this->conn->prepare($sql);
		# 绑定参数
		if(args($param,[],'a'))
			array_walk($param, function(&$v,$k) use($sth){$sth->bindParam($k,$v,self::pdoTe($v));});
		# 执行语句
		if($sth->execute()) return $sth;
		$err = $sth->errorInfo();
		throw new Exception('Database SQL: "' . $sql. '", ErrorInfo: '. $err[2]);
	}

	private function dbInstance( $sm = false )
	{ # 链接主从
		list($conf,$k) = $sm && !empty($GLOBALS['mysql']['SLAVE'])?
			[ $GLOBALS['mysql']['SLAVE'][($k = array_rand($GLOBALS['mysql']['SLAVE']))],'slave_'.$k]:
			[ $GLOBALS['mysql'], 'master'];
		if(empty($this->link[$k])){
			try {
				$this->link[$k] = new PDO(
					'mysql:dbname='.$conf['DB'].';host='.$conf['HOST'].';port='.$conf['PORT'],
					$conf['USER'],
					$conf['PASS'],
					[PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES \''.$conf['CHARSET'].'\'']);
			}catch(PDOException $e){throw new Exception('Database Err: '.$e->getMessage());}
		}
		return $this->link[$k];
	}
}