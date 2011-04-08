<?php

class Jcache {
    /**
     * 当前时间
     */
    protected $_time = '';

    /**
     * 缓存更新时间
     */
    
    private $updateTime = '0';
    
    /**
     * 过期时间(秒)
     */
    public $expire = '600';
    
    public $hosts = array();
    
    
    public function __construct($hosts) {
        $this->hosts = $hosts;
        $this->_time = time();
    }
    
    
    /**
     * Get memcache object
     *
     * @return object
     */
    public function getMemcacheObj() {
        static $memObj;
        if(!$memObj){
            if (!is_array($this->hosts) || empty($this->hosts)){
                return null;
            }
            $memcache = new Memcache();
            foreach($this->hosts as $host){
                if(isset($host[1])){
                    $memcache->addServer($host[0], $host[1]);
                } else {
                    $memcache->addServer($host[0], MEMSERVER_DEFAULT_PORT);
                }
            } 
            $memcache->setCompressThreshold(10000, 0.2);
            $memObj = $memcache;
        }
        return $memObj;
    }

    /**
     * Set variable to memcache 
     * 
     * @param $key
     * @param $value
     * @param $flag
     * @param $expire
     * @return bool
     */
    public function set($key, $value, $expire = 0) {
        if(empty($key)) {
            return false;
        }
        $memObj = self::getMemcacheObj();
        return $memObj->set($key, $value, false, $expire);

    }

    /**
     * Fetch variable from memcache
     *
     * @param $key
     * @return false or null
     */
    public function get($key) {
        $memObj = self::getMemcacheObj();
        return $memObj->get($key);
    }

    /**
     * Replace variable by memcache
     *
     * @param $key
     * @param $value
     * @return bool
     */
    public function replace($key, $value, $expire = 0) {
        $memObj = self::getMemcacheObj();
        return $memObj->replace($key, $value, false, $expire);
    }

    /**
     * Delete variable from memcache
     *
     * @brief
     * @param $key
     * @return bool
     */
    public function remove($key) {
        $memObj = self::getMemcacheObj();
        return $memObj->delete($key);
    }

    /**
     * 获取更新时间
     */
    public function getUpdateTime($key){
        return $this->get($key) ? $this->get($key) : 0;
    }
    
    /**
     * 设置更新时间
     */
    public function setUpdateTime($key,$value) {
        return $this->get($key) ? $this->replace($key, $value) : $this->set($key,$value);
    }
}