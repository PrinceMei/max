<?php
namespace Max;

use \Max\Config;
use PDO;

Class Models {
    private static $db;

    public function __construct() {
        $config = \Max\Config::get('mysql');
        if(!is_null($config)) {
            $port = $config['port'];
            //myql默认端口
            if(is_null($port) || empty($port)) {
                $port = 3306;
            }
            $dsn = 'mysql:host=' . $config['host'] .
                ';dbname=' . $config['dbname'] .
                ';port=' . $port .
                ';connect_timeout=10';
            $this->db = new PDO($dsn, $config['user'], $config['password']);
        }
    }

    //返回多条记录
    public function query($sql) {
        $res = $this->db->query($sql);
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }

    //返回单条记录
    public function queryOne($sql) {
        $res = $this->db->query($sql);
        return $res->fetch(PDO::FETCH_LAZY);
    }

    //返回记录总数
    public function queryCount($sql) {
        $res = $this->db->prepare($sql);
        $res->execute();
        return $res->rowCount();
    }

    //插入记录
    public function insert($table, $data) {
        $fileds = array();
        $values = array();
        foreach($data as $key => $val) {
            array_push($fileds, '`' . $key . '`');
            if(is_numeric($val)) {
                array_push($values, $val);
            } else {
                array_push($values, '"' . $val . '"');
            }
        }
        $sql = "INSERT INTO " . $table . " (" . implode(', ', $fileds) . ") VALUES (" . implode(', ', $values) . ")";
        $this->db->exec($sql);
        return $this->db->lastInsertId();
    }

    //删除记录
    public function delete($sql) {
        $res = $this->db->prepare($sql);
        $res->execute();
    }
}

