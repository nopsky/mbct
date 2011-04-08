<?php
/**************************************************
*  Created:  2011-4-7
*
*  网站入口文件
*
*  @Bct (C)1996-2099 Bct Inc.
*  @Author nopsky <cnnopsky@gmail.com>
*
***************************************************/

//定义根目录
define('ROOT', str_replace('\\', '/', getcwd()));

//定义项目文件目录
define('M_PATH','www/');

//加载配置文件
include ROOT.'/config/config.php';

//加载框架文件
include ROOT.'/app/app.php';

//执行框架入口函数
$app = App::getInstance();

$app->run($config);