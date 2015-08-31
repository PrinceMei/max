<?php
/*
 * 每个中间件为一个独立的方法，可以在route中使用。
 * 中间件可以使用Models目录下的任意类
 * TODO middle中引用request、response等对象
 */
function auth(){
    //此处做了桥接，返回的为slim的app实例
    $app = \Max\App::getInstance();
    //获取slim request对象：$app->request;
    //获取slim response对象：$app->response;
    $userModel = new \Models\User();
    $data = $userModel->authUser();

    echo '<div>auth middleware: '. $data .'</div>';
}