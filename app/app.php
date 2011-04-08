<?php
/**************************************************
*  Created:  2011-4-7
*
*  框架入口文件
*
*  @Bct (C)1996-2099 Bct Inc.
*  @Author nopsky <cnnopsky@gmail.com>
*
***************************************************/

if(!defined('ROOT')) {
	exit('Access Deined');
}
class App {
	
	/**
	 * 
	 * 单例对象
	 * @var $instance
	 */
	private static $instance ;
	
	/**
	 * 
	 * 站点配置参数
	 * @var $config
	 */
	private static $config = '';

	/**
	 * 
	 * 禁止使用构造函数
	 */
	private function __construct(){
		
	}
	
	/**
	 * 禁止复制对象
	 */
	private function __clone(){
		
	}
	
	/**
	 * 单例模式
	 * 
	 * @return obj
	 */
	public static function getInstance() {
		if(!isset(self::$instance)) {
			$class = __CLASS__;
			self::$instance = new $class();
		}
		return self::$instance;
	}
	
	//框架执行函数
	public function run($config){
		//初始化配置
		$this->_initConfig($config);
		
		//初始化变量
		$this->_initParam();
		
		//访问控制检查
		$this->_aclCheck();

		//初始化路由
		$this->_initRoute();
	}
	
	
	public function _initConfig($config){
		//设置时区
		date_default_timezone_set('Etc/GMT+8');
		//不进行魔术过滤
		set_magic_quotes_runtime(0);
		
		//加载系统配置
		require(ROOT.'/app/sys_config.php');
		
		//加载控制器基础类
		require(ROOT.'/app/control.php');
		
		//加载数据层基础类
		require(ROOT.'/app/model.php');
		
		APP::$config = $config;
	}
	
	//初始化变量
	public function _initParam(){
		// 过滤 GPC
		if(!get_magic_quotes_gpc()) {
			$this->daddslashes($_GET);
			$this->daddslashes($_POST);
			$this->daddslashes($_COOKIE);
		}
	}
	
	//路由分发
	public function _initRoute(){
		if(REWRITE_ENABLE) {
			$ss = trim($_SERVER['PATH_INFO'],'/');
			if(empty($ss)) {
				$m = DEF_MOD;
				$a = DEF_ACT;	
			} else {
				$urlarr = explode('/',$ss);
				$m = !empty($urlarr['0']) ? $urlarr['0'] : DEF_MOD;
				$a = !empty($urlarr['1']) ? $urlarr['1'] : DEF_ACT;
				$urlarr = array_slice($urlarr,2);
				if(!empty($urlarr)) {
					$count = count($urlarr);
					for($i=0; $i<=$count; $i++){
						$_GET[$this->daddslashes($urlarr[$i])] = $this->daddslashes($urlarr[$i+1]);
					}
				}
			}
		} else {
			$m = isset($_GET['m']) ? $_GET['m'] : DEF_MOD;
			$a = isset($_GET['a']) ? $_GET['a'] : DEF_ACT;
		}
		//获取当前项目所在目录
		if(include(APP_C.'/'.M_PATH.$m.'.php')) {
			$control = new $m();
			if(method_exists($control, $a)) {
				$r = new ReflectionMethod($control, $a);
				if($r->getDeclaringClass()->name == 'base' || $r->isProtected() || $r->isPrivate() ) {
					exit('forbidden');
				} else {
		 			$control->$a();
				}
			} else {
				$control->__call($a, NULL);
			}
		} else {
			header("Location://".$_SERVER['HTTP_HOST']);
		}
	}
	
	//访问控制器
	public function _aclCheck(){
		
	}
		
	/**
	 * 根据用户服务器环境配置，递归转义
	 * @param $mixed
	 * @return 转义后的值
	 */
	public function daddslashes($mixed) {
		if(is_array($var)) {
			foreach($var as $k=>&$v) {
				addslashes($v);
			}
		} else {
			$var = addslashes($var);
		}
	}
}