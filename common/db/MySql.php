<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

namespace Targoman\AlertManager\common\db;

use PDO;

class MySql {
    public $host = "127.0.0.1";
    public $port = "3306";
    public $username;
    public $password;
    public $schema;

    static $PDO = null;

    public function init() {
        if (empty($this->username))
            throw new \Exception("username is empty");
        if (empty($this->password))
            throw new \Exception("password is empty");
        if (empty($this->schema))
            throw new \Exception("schema is empty");
    }

    private function getDSN() {
        return "mysql:host=$this->host;port=$this->port;dbname=$this->schema";
    }

    public function getPDO() {
        if (self::$PDO == null) {
            self::$PDO = new \PDO($this->getDSN(), $this->username, $this->password);
            self::$PDO->exec('SET NAMES "utf8mb4"');
        }

        return self::$PDO;
    }

    public function createCommand($_qry) {
        return new Command($this, $_qry);
    }

    public function selectAll($_qry) {
        $command = $this->createCommand($_qry);

        return $command->queryAll();
    }

    public function selectOne($_qry) {
        $command = $this->createCommand($_qry);

        return $command->queryOne();
    }

}