<?php
/**
 * @ MYSQL Master/Slave PDO操作类
 */

class pdo_mysql {
    
    /**
     * 数据库字符集
     * @var string
     */
    public $dbCharset = 'UTF8';

    
    public $_table = '';
    /**
     * 
     *  数据库查询次数
     */
    private $queryNum = 0;

    /**
     * Master 数据库连接参数
     * @var array
     * @example array(host,user,pwd,db);
     */
    private $wConf = array();

    /**
     * Master 数据库连接
     * @var object
     */
    private $wConnDb = null;
    
    /**
     * Slave 数据库参数
     * @var array
     * @example array(
     * 				array(host1,user1,pwd1,db1),
     * 				array(host2,user2,pwd2,db2)
     * )
     */
    private $rConf = null;

   /**
    * Slave 数据库连接
    * @var array()
    */
    private $rConnDb = array();
    
    /**
     * 当前数据库连接
     * @var object
     */
    private $pdo = null;
    
    /**
     * SQL日志
     * @var number
     * 0:不记录
     * 1:记录
     */
    public $logSqlLevel = 1;
    
    /**
     * 日志存放路径
     * @var string
     */
    public $logSqlFile = './error.log';
    
    /**
     * MySQL错误消息
     */
    private $errMsg = '';
    
    /**
     * MYSQL 执行时间
     * @var number
     */
    private $runTime = 0;
    
    public  $total = 0;
    
    /**
     * 
     * @var unknown_type
     */
    private $singleHost = true;
    
    /**
     * 是否打开调试
     * @var number
     */
    public  $isDebug = 1;

    public function _set($name, $value) {
        $this->$name = $value;
    }
    
    public function _get($name) {
        if(isset($this->$name)){
            return $this->$name;
        }
        return null;
    }

    /**
     * 初始化数据库配置文件
     * @param array $masterConf
     * @param array $slaveConf
     */
    public function __construct($masterConf, $slaveConf=array()){
        if(is_array($masterConf) && !empty($masterConf)){
            $this->wConf = $masterConf;
        }
        if(is_array($slaveConf) && !empty($slaveConf)) {
            $this->rConf = $slaveConf;
        } else {
            $this->rConf = $masterConf;
        }
    }
    
    /**
     * 获取master的连接
     */
    private function getwConnDb() {
        if($this->wConnDb && is_object($this->wConnDb)) {
            return $this->wConnDb;
        }
        $this->wConnDb = $this->connect($this->wConf);
        if($this->wConnDb && is_object($this->wConnDb)) {
            return $this->wConnDb;
        } else {
            return false;
        }
    }
    
    /*
     * 获取Slave的连接
     */
    private function getrConnDb() {
        //如果有可用的Slave连接，随机挑选一台Slave   
        if (is_array($this->rConnDb) && !empty($this->rConnDb)) {   
            $key = array_rand($this->rConnDb);   
            if (isset($this->rConnDb[$key]) && is_object($this->rConnDb[$key])) {   
                return $this->rConnDb[$key];   
            }   
        }   
        //连接到所有Slave数据库，如果没有可用的Slave机则调用Master     
        if (!is_array($this->rConf) || empty($this->rConf)){   
            return $this->getwConnDb();   
        }   

        foreach($this->rConf as $tmp_rConf){   
            $db = $this->connect();   
            if ($db && is_object($tmp_rConf)){   
                $this->rConnDb[] = $db;   
            }   
        }   
        //如果没有一台可用的Slave则调用Master   
        if (!is_array($this->rConnDb) || empty($this->rConnDb)){   
            $this->errorLog("Not availability slave db connection, call master db connection");   
            return $this->getwConnDb();   
        }   
        //随机在已连接的Slave机中选择一台   
        $key = array_rand($this->rConnDb);   
        if (isset($this->rConnDb[$key])  && is_object($this->rConnDb[$key])){   
            return $this->rConnDb[$key];   
        }   
        //如果选择的slave机器是无效的，并且可用的slave机器大于一台则循环遍历所有能用的slave机器   
        if (count($this->rConnDb) > 1){   
            foreach($this->rConnDb as $conn){   
                if (is_object($conn)){   
                    return $conn;   
                }   
            }   
        }   
        //如果没有可用的Slave连接，则继续使用Master连接   
        return $this->getwConnDb();  
    }
    
    /**
     * 数据库连接
     */
    private function connect($Conf) {
    	try {
			$dsn = (isset($Conf['dbtype']) ? $Conf['dbtype'] : 'mysql').':dbname='.$Conf['db'].';host='.$Conf['host'].';port='.(isset($Conf['port']) ? $Conf['port'] : '3306');
			$this->pdo = new PDO($dsn, $Conf['user'], $Conf['pwd']);
			$this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);
			if(!empty($this->dbCharset)) {
				$this->pdo->query("SET NAMES '".$this->dbCharset."'");
			}
			return $this->pdo;
		}catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();exit;
		    $this->errorLog('Connection failed: ' . $e->getMessage());
		}
    }
    
    /**
     * SQL查询语句
     * @param string
     * @param array('fields'=>array(),'where'=>'','order'=>''....);
     * @return string;
     */
    public function select($condition) {
        $fields = '*';
        if(isset($condition['fields']) && is_array($condition['fields'])) {
            $fields = implode(',', $condition['fields']);
        }
        $sql = 'SELECT '.$fields.' FROM '.$this->getTable().' '.$this->buildCondition($condition);
        return $this->_query($sql);
    }
    
    /**
     * 更新操作
     * @param string
     * @param array('fiedls'=>array(),'where'=>'','order'=>'');
     * @return mix;
     */
    public function update($condition) {
        $sql = 'UPDATE '.$this->getTable().' SET ';
        $_content = $comm = '';
        if(is_array($condition['fields']) && !empty($condition['fields'])) {
            foreach($condition['fields'] as $k=>$v) {
                $v = $this->escape_sql($v);
                if(substr($v,0,1) == '+' || substr($v,0,1) == '-') {
                    $_content .= $comm." `$k` = $k$v ";
                } else {
                    $_content .= $comm." `$k` = '$v' ";
                }
                $comm = ',';
            }
            $condition = $this->buildCondition($condition);
            $sql = $sql.$_content.$condition;
            $this->_query($sql);
        } else {
            return null;
        } 
    }
    
    public function delete($condition) {
        $condition = $this->buildCondition($condition);
        if(empty($condition)) {
            return false;
        }
        $sql = 'DELETE FROM '.$this->getTable().' '.$this->buildCondition($condition);
        $this->_query($sql);  
    }
    
    public function insert($condition) {
        $sql = 'INSERT INTO '.$this->getTable();
        $_content =  '';
        if(is_array($condition['fields']) && !empty($condition['fields'])) {
            $_content = implode("`,`",$condition['fields']); 
            $sql = $sql."(`$_content`) VALUES "; 
        } else {
            return false;
        }
        
        $_vcontent = $comm = '';
        if(is_array($condition['values']) && !empty($condition['values'])) {
            foreach($condition['values'] as $vv) {
                $vv = implode("','",array_map('addslashes',$vv));
                $_vcontent .= $comm."('$vv')";
                $comm = ',';
            }
            $sql = $sql.$_vcontent; 
        } else {
            return false;
        }
        $this->_query($sql);
    }

    public function _query($sql) {
        $this->queryNum++;

        if(!$this->singleHost) {
            $optType = trim(strtolower(substr(ltrim($sql), 0, 6))); 
        }
        
        if ($this->singleHost || $optType!="select"){   
            $dbConn = $this->getwConnDb();   
        } else {   
            $dbConn = $this->getrConnDb();   
        }
        if(!$dbConn || !is_object($dbConn)) {
            $this->errorLog("Not availability db connection. Query SQL:". $sql);
            return false;
        }

        $this->pdo = $dbConn;
        $startTime = $this->getTime();   
		$sth = $this->pdo->query($sql);
		if($this->logSqlLevel) {
		    $this->errorLog($sql);
		}
        $this->runTime = $this->getTime() - $startTime;
        return $sth;
    }

    public function rowCount(){
        return $this->total;
    }

    public function getRow($condition) {
    	$sth = $this->select($condition);
 		$sth->setFetchMode(PDO::FETCH_ASSOC);
 		$this->total = $sth->rowCount();
		$result = $sth->fetch();
		return $result;
    }
    
    public function getAll($condition) {
        $sth = $this->select($condition);
        $sth->setFetchMode(PDO::FETCH_ASSOC);
        $this->total = $sth->rowCount();
		return $sth->fetchAll();
    }
    
    public function getInsertId() {
        return $this->pdo->lastInsertId();
    }
   
 
    private function getTable() {
        return $this->_table;
    }
    
    public function setTable($table=null) {
        if(!empty($table)) {
            $this->_table = $table;
        }
    }
    /**
     * 构建条件语句
     * @param mix
     * @example:
     * $condition = array('where'=>"id=1 and sid>2 or did<3",
     * 		'order'=>'dateline desc, id asc', 
     * 		'group'=>'total',
     * 		'limit'=>'10,20',
     * 		'leftjoin'=>'tb_user tu',
     * 		'union'=>array('tb_user tu'=>$condition),
     * 		'rightjoin'=> 'tb_user tu',
     * 		'having'=>'total > 10'
     * );
     */
    private function buildCondition($condition) {
        if(is_string($condition) || is_null($condition)) {
            return $condition;
        }
        $content = null;
        foreach($condition as $k=>$v) {
            $k = strtolower($k);

            if(in_array($k, array('leftjoin', 'rightjoin'))) {
                $content .= ' '.$v;
                continue;
            }

            if($k == 'where' && is_string($v)) {
                $content .= ' WHERE '.$v;
                continue;
            }
            
            if($k == 'group' && is_string($v)) {
                $content .= ' GROUP BY '.$v;
                continue;
            }
            
            if($k == 'having' && is_string($v)) {
                $content .= ' HAVING '.$v;
                continue;
            }
            
            if($k == 'union' && is_array($v)) {
                $content .= ' '.$this->select($k,$v);
                continue;
            }
            
            if($k == 'order' && is_string($v)) {
                $content .= ' ORDER BY '.$v;
                continue;
            }
            
            if($k == 'limit' && is_string($v)) {
                $content .= ' LIMIT '.$v;
                continue;
            }
        }
        return trim($content);
    }
    
    public function escape_sql($string) {
    	if (get_magic_quotes_gpc()){
			$string = stripslashes($string);
		}
        return addslashes($string);
    }
 
    /**  
     * 获取执行时间  
     *  
     * @return float  
     */  
    public function getRunTime(){   
        if ($this->isRuntime){   
            return sprintf("%.6f sec",$this->runTime);   
        }   
        return 'NULL';   
    }   
     /**  
     * 获取当前时间函数  
     *  
     * @param void  
     * @return float $time  
     */  
    public function getTime(){   
        list($usec, $sec) = explode(" ", microtime());   
        return ((float)$usec + (float)$sec);   
    }

    /**  
     * 错误日志  
     */  
    function errorLog($msg='', $conn=null){   
        if ($this->logSqlLevel == 0){   
            return;   
        }   
        if ($msg=='' && !$conn) {   
            return false;   
        }   
        $log = "MySQL Error: $msg";   
        if ($conn && is_object($conn)) {   
            $log .= " mysql_msg:". mysql_error($conn);   
        }   
        $log .= " [". date("Y-m-d H:i:s",time()) ."]";   
        if ($this->logSqlFile != ''){   
            error_log($log ."\n", 3, $this->logSqlFile);   
        } else {   
            error_log($log);   
        }   
        return true;   
    }
}