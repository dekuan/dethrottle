# dethrottle
throttle

一、 安装
1、composer require predis/predis


2、复制文件  CustomThrottle.php 到  app\Http\middleWare\
修改文件的 getUMid()和getMobile（）两个方法，使之能正确获取项目里的用户MId个手机号码，
如果服务器之前有代理，还需要修改getIp（），保证获取正确Ip地址

3、修改 app\Http\Kernel.php
注释掉 api里的配置( )throttle:"",60,1

在 $routeMiddleware 里修改throttle的配置为
'throttle' => CustomThrottle::class
添加namespace  
use App\Http\Middleware\CustomThrottle;


二、使用：
使用方法类似下面：
'middleware' => 'throttle:inter,60,1
第一个参数：inter 可使用 ''（使用laravel提供的方式）, ip（id地址）,umid（用户MID）, sms（短信）, inter（接口名称）代替
第二个数字表示最大访问次数
第三个参数代表时间，单位：分钟