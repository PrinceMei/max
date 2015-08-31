<?php
namespace Models;
class User extends \Max\Models {
    public function getUser() {
        $sql = 'select * from user where id > 0 order by id desc limit 1';
        $result = $this->queryOne($sql);

        return $result;
        $this->insert('user', array(
            'username' => 'cosa',
            'password' => 'password',
            'age' => 40
        ));
    }

    public function authUser(){
        return 'AuthUser';
    }
}