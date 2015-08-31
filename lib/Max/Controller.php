<?php
namespace Max;
abstract class Controller {
    protected $app;

    public function __construct(\Slim\Slim &$app) {
        $this->app = $app;
    }

    //获取get请求参数
    private function getParamsData($name = null, $paramsData = null) {
        if(empty($paramsData)) {
            return null;
        }
        //编码
        foreach($paramsData as $key => $val) {
            $paramsData[$key] = $this->encodeParamData($val);
        }
        //不指定参数名（或者参数为空）时获取全部参数
        if(is_null($name) || empty($name)) {
            return $paramsData;
        }
        //name为字符串
        if(is_string($name)) {
            return $paramsData[$name];
        }
        //name为数组
        $result = array();
        foreach($name as $n) {
            if(!is_null($n) && (!is_array($n) || !empty($n))) {
                $result[$n] = $paramsData[$n];
            } else {
                $result[$n] = null;
            }
        }
        return $result;
    }

    //编码参数值（转义什么的）
    private function encodeParamData($value) {
        return preg_replace('/>/', '&gt;', preg_replace('/</', '&lt;', $value));
    }

    //渲染模板（扩展名.html）
    protected function render($template, $args = array()) {
        if(!preg_match('/\.html$/', $template)) {
            $template .= '.html';
        }
        $this->app->render($template, $args);
    }

    //重定向页面
    protected function redirect($url) {
        $this->app->redirect($url);
    }

    //获取request对象
    protected function request() {
        return $this->app->request();
    }

    ///获取get参数
    protected function getParams($name = null) {
        $paramsData = $this->app->request->get();
        return $this->getParamsData($name, $paramsData);
    }

    //获取post参数
    protected function postParams($name = null) {
        $paramsData = $this->app->request->post();
        return $this->getParamsData($name, $paramsData);
    }

    //获取put参数
    protected function putParams($name = null) {
        $paramsData = $this->app->request->put();
        return $this->getParamsData($name, $paramsData);
    }
}