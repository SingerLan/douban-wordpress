# douban

#### 介绍
采集豆瓣“我看过的电影”，整合到wordpress
本来想写一个wordpres插件，结果没有时间学加上懒，就没有写成插件

#### 截图
![输入图片说明](https://images.gitee.com/uploads/images/2018/1227/115854_0f39d1ca_2322897.png "m.lookoro.cn_index.php_douban_movie (2).png")

#### 演示地址
[http://m.lookoro.cn/index.php/douban_movie](http://m.lookoro.cn/index.php/douban_movie)

#### 安装教程（不是插件，需要修改PHP文件）

- 下载clone文件到本地
- 修改douban.php文件439行440行
```
$UserID="181244075";//我的豆瓣ID
$PageSize=20;//一页显示20部电影
//UserID：用户ID，在豆瓣中可以查到
//PageSize：自己随意设置
```
- 将下载douban文件夹上传到wordpress根目录
- 现在就可调用php文件，调用URL /douban/douban.php?type=movie&from=0 
- 以上步骤即可调用到“我看过的电影”，如需整合到wordpress需继续配置
- 新建wordpress页面
- 如支持自定义栏目，名称中插入head 值中插入asset中的js css文件，如不支持接步骤8
- 我的主题不能插入head自定义栏目，我都是通过js加载的
```
<h1 style="text-align: center;">我看过的电影</h1>
<script type="text/javascript">
    var head = document.getElementsByTagName('head')[0];
    var link = document.createElement('link');
    link.type='text/css';
    link.rel = 'stylesheet';
    link.href = '//m.lookoro.cn/douban/assets/DoubanBoard.04.css';
    head.appendChild(link);
</script>
<script>var DoubanPageSize=20;</script>
<script type="text/javascript" src="//m.lookoro.cn/douban/assets/DoubanBoard.04.js"></script>
<div id="douban-movie-list" class="doubanboard-list" style="margin-top: -70px;"></div>
```
DoubanPageSize 页面中的显示多少部电影
douban-movie-list 显示电影海报的div，加“margin-top: -70px;”是因为上方js占位
9. 发布页面

#### 使用说明

1. 需保证cache可写
2. 使用前先删除movie.json文件，json文件已储存我的影单
3. 也可获取单部电影详情、单部书籍、读书清单等, 方法保留，可以调用

#### 感谢

1. 基本借鉴于 [熊猫小A](http://https://github.com/AlanDecode) Typecho-Plugin-DoubanBoard插件
