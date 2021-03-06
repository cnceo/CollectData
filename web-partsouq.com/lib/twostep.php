<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

/**
  * @author xu
  * @copyright 2018/01/29
  */
class twostep{

	// 车系=》车
	public static function market()
	{
		// 下载所有的market页面
		Capsule::table('url_market')->where('status','wait')->orderBy('id')->chunk(20,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_market', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	$guzzle = new guzzle();
		    	$guzzle->down('url_market',$data);
		    }
		});

		// 获取所有的车连接
		Capsule::table('url_market')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			$prefix ='https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'url_market/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 是否有筛选框
						if($dom->find("#model-filter-form",0))
						{
							// 有筛选框
							// 校验是否有 101 shown字眼，如果有的话将该页面标记为last
							if($dom->find("#content .container h3",0))
							{	
								// 获取数字
								preg_match("/\d+/", $dom->find("#content .container h3",0)->innertext,$num);
								if(current($num) < 100 && current($num)>0)
								{
									// 此时已经是最终页面设置为last
									Capsule::table('url_market')
							            ->where('id', $data->id)
							            ->update(['status' => 'last']);
							        // 退出当前循环
							        continue;
								}
							}
							// 此时还不是最终链接，只能拼接下一级的下拉框获取新的url
							// 获取第一个下拉框选项拼接url的所有结果集 
							$name = $dom->find('#model-filter-form .cat_flt_',$data->level)->name;
							$temp = array();
							foreach ($dom->find('#model-filter-form .cat_flt_',$data->level)->find("option") as $value)
							{
								if($value->value!="")
								{
									$temp[] = [
												'status' => 'wait' ,
												'url' => $data->url.'&'.$name.'='.$value->value,
												'md5_url' => md5($data->url.'&'.$name.'='.$value->value),
												'level' => $data->level+1
											];
								}
							}
							// 插入所有数据
							Capsule::table('url_market')->insert($temp);
							// 删除原来连接
							Capsule::table('url_market')->where('id', '=', $data->id)->delete();
							// 如果已经解析需要把这个文件删除
							unlink($file);
						}
						else
						{
							// 没有下拉选项不做处理，此刻已经显示所有的car链接
							// 将状态设置为last
							Capsule::table('url_market')
					            ->where('id', $data->id)
					            ->update(['status' => 'last']);
						}
					}
		    	}
		    }
		});

		// 获取需要下载的页面
		$wait = Capsule::table('url_market')
            ->where('status', 'wait')
           	->count();
        echo "still have item need to download ,sum : ".$wait."\r\n";

	}
}