# Max(base on Slim Framework)

* [slim](http://docs.slimframework.com/)

## changelog
* 8.24
    * Add public folder.
    * Add middleware document.

## Install
1. Install nginx（apache）、mysql、php5;
2. Install composer [https://getcomposer.org/download/](https://getcomposer.org/download/);
3. Clone the repository into your workspace;
4. Install dependence: `composer install`;
5. Config nginx(or apache):

    nginx sett：
    ```
    server {
        listen 80;
        server_name yourdomain.com; # replace with your domain
        index index.php;
        root  /workspace; #your workspace

        #rewrite all request to index.php
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
6. Open http://yourdomain.com with your browser.


## Useage

Max base on Slim Framework, Add some useful feature such as MVC,System Config, Route Config and so on.

### App Config

You can modify App information in `config.json`:
```
{
    // App Config
    "app": {
        "debug": true,
        "cookies.lifetime": "1 days",
        "cookies.httponly": true,
        "cookies.secret_key": "CDD4DEBDAD28A6A7043B84D4737BFF2B"
    },
    // Mysql Config
    "mysql": {
        "host": "127.0.0.1",
        "port": 3306,
        "dbname": "ebuilder",
        "user": "root",
        "password": "root"
    }
}
```

### Models
Please put all of your models in `Models` directory, and keep the same name as the class name and file(capitalized)

Exp:
```
file: User.php
<?php
// use Models as namespace
namespace Models;
// inherited from \Max\Models
class User extends \Max\Models {
    // add getUser method to User
    public function getUser() {
        echo 'Hello User~';
    }
}
```

use User Models in your controller

```
// get instance of User Models
$userModel = new \Models\User();
// call getUser method of User instance.
$a = $userModel->getUser();
```

### Controller

Please put all of your controller in `Controller` directory, and keep the same name as the class name and file(capitalized)

Exp:
```
file: Home.php
<?php
// use Controller as namespace
namespace Controller;
// inherited from \Max\Controller
class Home extends \Max\Controller {
    // add index method to Home
    public function index() {
        // get instance of User
        $userModel = new \Models\User();
        // call getUser method of User
        $a = $userModel->getUser();
        echo $a;
    }
}
```
modify `route.json` as:
```
{
    "/": "Home:index"
}
```

When "/" was accessed, Max will load Home Class from `/Controller/Home.php` and processing requests using `index` method. 


### View(Template)

Max use twig as template [http://twig.sensiolabs.org/](http://twig.sensiolabs.org/)

Please put your template in `Views` directory, wich extension of html.

Use `$this->render` to render your template in controller. Exp：

```
<?php
// use Controller as namespace
namespace Controller;
// inherited from \Max\Controller
class Home extends \Max\Controller {
    // add index method
    public function index() {
        // get instance of User
        $userModel = new \Models\User();
        // call getUser method of User
        $a = $userModel->getUser();
        // render Vites/home.html
        $this->render('home', array(
            'title' => $title,
            'name' => '[home index]'
        ));
    }
}
```

### Route

Config your route in `route.json`, Max now support `GET`、`POST`、`PUT`、`PATCH`、`DELETE`. Exp:

* Simple GET method
```
{
    // processing request of "/index.html" with index method in Controller/Home.php
    "/index.html": "Home:index"
}
```
* Complete style with middleware
```
{
    "/index.html": {
        // middleware, string as single middle. array for multiple middleware
        "middleware": "authAdmin",
        // action list
        "action": {
            // action of each method
            "get": "Home:getIndex",
            "post": "Home:postIndex",
            "delete": "Home.deleteIndex"
        }
    }
}
```

* GET Request with middleware

```
{
    "/index.html": {
        // middleware, string as single middle. array for multiple middleware
        "middleware": "authAdmin",
        "action": "Home:getIndex"
    }
}
```

* part of url need middleware
```
{
    "/index.html": {
        // GET request without middleware
        "get": "Home:getIndex"
        // POST request with middleware
        "post": {
            "middleware": "authAdmin",
            "action": "Home:postIndex"
        }
    }
}
```

* Route Group
```
"/admin": {
    "/name": "Home:index",
    "/title": {
        "middleware": "auth",
        "action": "Home:index"
    }
}
```


### Middleware

Your can add method of middleware to `middleware.php`, in middleware use `$app = \Max\App::getInstance();` to get instance of app.

```
function auth(){
    // get instance of app.
    $app = \Max\App::getInstance();
    
    if($app->request->isGet()){
        echo '当前为get请求'；
    }
    // get response of slim: $app->response;
    $userModel = new \Models\User();
    $data = $userModel->authUser();
    
    echo '<div>auth middleware: '. $data .'</div>';
}
```



## Dependence
* slim/slim: "2.6.2",
* slim/extras: "2.0.3",
* slim/views: "0.1.3",
* twig/twig: "1.20.0"
