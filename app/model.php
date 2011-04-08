<?php
/**************************************************
*  Created:  2011-4-8
*
*  数据库处理层
*
*  @Bct (C)1996-2099 Bct Inc.
*  @Author nopsky <cnnopsky@gmail.com>
*
***************************************************/

class model {
	/**
	 * 单例对象
	 * 
	 * @var $instance
	 */
	private static $instance ;
	
	/**
	 * 数据库对象
	 * 
	 * @var $db
	 */
	protected $db = '';
	
	/**
	 * 缓存对象
	 * 
	 * @var $cache
	 */
	protected $cache = '';
	
	
	private function __construct(){}
	
	private function __clone(){}
	
	/**
	 * 单例模式
	 * 
	 * @return obj
	 */
	public function getInstance(){
		if(!isset(self::$instance)) {
			$class = __CLASS__;
			self::$instance = new $class();
		}
		return self::$instance;
	}
	
    /**
     * 数据库对象处理
     */
    public function getDb(){
        try{
            if(isset($this->db) && is_object($this->db)) {
                return $this->db;
            }
            require(APP_L.'/pdo_mysql.class.php');
            $db = new pdo_mysql(App::$config['Db']['Master'],App::$config['Db']['Slave']);
            $db->setTable($this->_table);
            return $db;
        } catch(exception $e) {
            throw $e ;
        }
    }
    
    /**
     * 缓存对象处理
     */
    public function cache(){
        try{
            if(isset($this->cache) && is_object($this->cache)) {
                return $this->cache;
            }
           	require(APP_L.'/cache.class.php');
            $cache = new cache(App::$config['Cache']['Memcache']);
            return $cache;
        } catch(exception $e) {
            throw $e ;
        }
    }
    
    public function __set($name,$value=null) {
        $this->$name = $value;
    }

    public function __get($name) {
        switch($name){
            case 'db':
                $this->db = $this->getDb();
                return $this->db;
            case 'cache' :
                $this->cache = $this->cache();
                return $this->cache;
            default:
                throw new exception('Undefined property: ' . get_class($this). '::' . $name);
        }
    }
}
