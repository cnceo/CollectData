<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/twostep.php');
// 路径处理类
require_once('./lib/LibDir.php');
// 文件处理类
require_once('./lib/LibFile.php');

// 开始执行
twostep::initdetail();