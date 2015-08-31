<?php
namespace Controller;
class Home extends \Max\Controller {
    //url中“:”后面跟的参数默认作为参数传入
    public function index() {
        /*
        $d = new \Models\User();
        $a = $d->getUser();
        */
        /*
         * 获取get请求参数: $this->getParms(key)
         * 获取post请求参数: $this->postParms(key)
         * 获取put请求参数: $this->putParms(key)
         */
        $title = $this->getParams('title');
        $this->render('home', array(
            'title' => $title,
            'name' => 'test name'
        ));
    }
}