# Max(base on slim)使用文档

* [slim文档](http://docs.slimframework.com/)

## changelog
* 8.24
    * 增加public目录：
        * 该目录仅存放Max入口文件及静态资源文件文件。
        * 服务端部署时应该把此目录设置为网站根目录，避免暴漏不必要的服务端文件。
    * 增加中间件文档

## 安装
1. 搭建nginx（apache）、mysql、php环境；
2. 安装composer，文档：[https://getcomposer.org/download/](https://getcomposer.org/download/);
3. 复制当前目录下所有文件到工作目录；
4. 终端下进入上一步所在文件夹，执行：`composer install` 安装依赖
5. 设置nginx（apache请百度）：

    nginx虚拟主机设置：
    ```
    server {
        listen 80;
        server_name yourdomain.com; #请修改这里的域名
        index index.php;
        root  /workspace; #请修改此处为你的工作目录

        #最重要是下面这3行：把所有请求全部转到index.php
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php {
            fastcgi_connect_timeout 3s;     # default of 60s is just too long
            fastcgi_read_timeout 10s;       # default of 60s is just too long
            include fastcgi_params;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            fastcgi_pass 127.0.0.1:9000;
        }
    }
    ```
6. 打开浏览器访问上一步设置的：yourdomain.com


## 使用

Max底层基于slim框架，增加了系统配置、MVC以及更友好的路由。

### 系统设置

系统配置文件位于根目录下的`config.json`。相关字段说明如下：
```
{
    //app配置项
    "app": {
        //是否开启debug模式
        "debug": true,
        //cookie有效期
        "cookies.lifetime": "1 days",
        //cookie是否httponly
        "cookies.httponly": true,
        //cookie加密key
        "cookies.secret_key": "CDD4DEBDAD28A6A7043B84D4737BFF2B"
    },
    //mysql配置
    "mysql": {
        //mysql主机地址
        "host": "127.0.0.1",
        //mysql端口
        "port": 3306,
        //数据库名
        "dbname": "ebuilder",
        //数据库账号
        "user": "root",
        //数据库密码
        "password": "root"
    }
}
```

### Models
所有业务Models文件请放在根目录下的`Models`文件夹下，请保持类名与文件名一致（首字母大写）。

举个例子，在Models文件夹下增加User.php文件，内容如下：
```
<?php
//指定命名空间为Models
namespace Models;
//继承自\Max\Models，并且类名与文件名一致
class User extends \Max\Models {
    public function getUser() {
        echo 'Hello User~';
    }
}
```
在任意Controller中调用User类：
```
//实例化User
$userModel = new \Models\User();
//调用getUser方法
$a = $userModel->getUser();
```

### Controller

所有业务Controller文件请放在根目录下的`Controller`文件夹下，请保持类名与文件名一致（首字母大写）。

举个例子，在Controller文件夹下增加Home.php文件，内容如下：
```
<?php
//指定命名空间为Controller
namespace Controller;
//继承自\Max\Controller，并且类名与文件名一致
class Home extends \Max\Controller {
    //增加index方法
    public function index() {
        //实例化User
        $userModel = new \Models\User();
        //调用getUser方法
        $a = $userModel->getUser();
        echo $a;
    }
}
```
编辑`route.json`，修改内容为：
```
{
    "/": "Home:index"
}
```
其中`Home`为类名（以及文件名），冒号后面为类名中的`index`方法。

保存，并访问应用首页，没有错误的情况下会看到输出“Hello User~”。

### View(模板)
Max使用的模板引擎为twig，文档：[http://twig.sensiolabs.org/](http://twig.sensiolabs.org/)。

模板文件请放在根目录下的`Views`文件夹下，扩展名为.html。

Controller中可以使用`$this->render`方法渲染模板，Exp：

```
<?php
//指定命名空间为Controller
namespace Controller;
//继承自\Max\Controller，并且类名与文件名一致
class Home extends \Max\Controller {
    //增加index方法
    public function index() {
        //实例化User
        $userModel = new \Models\User();
        //调用getUser方法
        $a = $userModel->getUser();
        //渲染模板：Views/test.html。第二个参数为传递给模板引擎的数据
        $this->render('test', array(
            'title' => $title,
            'name' => '[home index]'
        ));
    }
}
```

### 路由（Route）

路由配置文件位于根目录下的`route.json`，内容为标准JSON格式。Max支持的请求类型包括：`GET`、`POST`、`PUT`、`PATCH`、`DELETE`。

* 简单的GET请求：
```
{
    //所有到网站根目录的请求使用Controller\Home类（也既Controller/Home.php文件）下的index方法来处理。
    "/index.html": "Home:index"
}
```
* 带中间件的完整请求：
```
{
    "/index.html": {
        //中间件有多个时值为数组，执行顺序与数组保持一致
        "middleware": "authAdmin",
        //存在middleware时需要额外增加action来指定请求类型
        "action": {
            //依次制定每种请求类型对应的action
            "get": "Home:getIndex",
            "post": "Home:postIndex",
            "delete": "Home.deleteIndex"
        }
    }
}
```

* 带中间件的GET请求（由上面的完整版精简而来）：

```
{
    "/index.html": {
        //中间件有多个时值为数组，执行顺序与数组保持一致
        "middleware": "authAdmin",
        //只有get请求时候可以直接把action的值设为get请求的action
        "action": "Home:getIndex"
    }
}
```

* 同一个请求，部分需要中间件：
```
{
    "/index.html": {
        //get请求不需要中间件
        "get": "Home:getIndex"
        //post请求需要中间件
        "post": {
            "middleware": "authAdmin",
            "action": "Home:postIndex"
        }
    }
}
```

* Route Group，目前只支持2级（/[parent]/[child]）。组内单条router的设置与上面一致。
```
"/admin": {
    // admin/name的get请求由Home类中的index处理
    "/name": "Home:index",
    // admin/name的get请求由Home类中的index处理
    "/title": {
        "middleware": "auth",
        "action": "Home:index"
    }
}
```


### 中间件（Middleware）

route中使用的全部中间对应于根目录下的`middleware.php`中的一个方法。

在中间件中可以通过 `$app = \Max\App::getInstance();` 拿到Slim运行的实例，然后就能方便的调用app下的方法：

```
function auth(){
    //此处做了桥接，返回的为slim的app实例
    $app = \Max\App::getInstance();
    
    if($app->request->isGet()){
        echo '当前为get请求'；
    }
    //获取slim response对象：$app->response;
    $userModel = new \Models\User();
    $data = $userModel->authUser();
    
    echo '<div>auth middleware: '. $data .'</div>';
}
```

### TODO
* ~~完善\Max\Models类下基本的sql查询方法~~
* ~~补充中间件功能以及文档~~
    * ~~中间件增加获取request、response等app属性的接口~~
* ~~增加asset目录~~
* ~~补充view继承demo~~



## 依赖(绝大多数情况下你不需要关系依赖)
* slim/slim: "2.6.2",
* slim/extras: "2.0.3",
* slim/views: "0.1.3",
* twig/twig: "1.20.0"