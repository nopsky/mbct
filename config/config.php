<?php
/**************************************************
*  Created:  2011-4-7
*
*  站点配置文件
*
*  @Bct (C)1996-2099 Bct Inc.
*  @Author nopsky <cnnopsky@gmail.com>
*
***************************************************/

$config = array();

/**
 * 数据库配置文件
 */
$config['Db']['Master'] = array('host'=>'127.0.0.1', 'user'=>'pdo', 'pwd'=>'a', 'db'=>'mbct');
$config['Db']['Slave'] = array();

/**
 * 缓存配置文件
 * @var unknown_type
 */
$config['Cache']['Type'] = 'memcache';

/**
 * Memcache配置文件
 */
$config['Cache']['Memcache'] = array(
                                   array('127.0.0.1','11211')
                                );

//页面编码
$config['Common']['CharSet'] = 'UTF-8';

//是否开始rewrite
define('REWRITE_ENABLE', 1);

//定义当前网站URL
define('SITE_URL', 'http://www.mbct.com');