<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;


/**
  * 解析所有列表页获取需要下载的详情页
  * @author xu
  * @copyright 2018/01/24
  */
class twostep{

	// 初始化表并解析入库
	public static function initdetail()
	{
		$LibFile = new LibFile();
		// 记录第2步骤日志
		$logFile = PROJECT_APP_DOWN.'twostep.txt';
		// 创建存储表
		Capsule::schema()->dropIfExists('url_detail');
		echo "url_detail delete\r\n";
		Capsule::schema()->create('url_detail', function (Blueprint $table) {
		    $table->increments('id');
		    $table->string('file_path');
		    $table->string('company_url');
		    $table->string('status');
		});
		echo "url_detail create\r\n";
		// 读取需要解析的本地HTML
		$aTable = Capsule::table('url_list')->get();
		// 闭包函数转为数组
		$aTable = $aTable->transform(function($aItem) {
		    return (array) $aItem;
		})->toArray();
		// 循环下载项
		foreach ($aTable as $sKey => $aVal)
		{
			// 需要读取的文件
			$file = PROJECT_APP_DOWN.'url_list/'.$aVal['ul_filename'].'.html';
			// 判定文件是否存在且为正常的文件
			if (is_file($file))
		    {
		    	$temp = file_get_contents($file);
				// 创建dom对象
				if($dom = HtmlDomParser::str_get_html($temp))
				{
					// 获取所有的详情页下载链接
					$articles = array();
					foreach($dom->find('div.noticeLotItem') as $article) {
					    $articles[] = $article->find('a',0)->href;
					}
					array_unique($articles);
					// 入库获取到的当前页面的详情页信息
					foreach ($articles as $v)
					{
						// 某一列表页url
						$data = array(
							// 保存路径批次+页码
							'file_path'=>$aVal['ul_filename'],
							// 页面地址
							'company_url'=>'http://www.cn357.com'.$v,
							'status'=>'wait'
						);
						// 插入记录
						Capsule::table('url_detail')->insert($data);
					}
					// 清理内存防止内存泄漏
					$dom-> clear();
					// 记录成功
				    $LibFile->WriteData($logFile, 4, $aVal['ul_filename'].'解析完成！');
					echo $aVal['ul_filename']." analyse ok!\r\n";
				}
				else
				{
					$LibFile->WriteData($logFile, 4, $aVal['ul_filename'].'解析失败！');
					echo $aVal['ul_filename']." analyse error!\r\n";
				}
		    }
		}
	}	
}