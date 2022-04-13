<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

namespace Targoman\AlertManager\common\db;

class Command {
    public $db;
    public $qry;
    public $pdoStatement;

    public function __construct($_db, $_qry) {
        if (empty($_qry))
            throw new \Exception("Query string is empty");

        $this->db = $_db;
        $this->qry = $_qry;
    }

    public function prepare() {
        if ($this->pdoStatement)
            return;

        $this->pdoStatement = $this->db->getPDO()->prepare($this->qry);
    }

    public function queryAll() {
        $this->prepare();

        $this->pdoStatement->execute();
        $result = $this->pdoStatement->fetchAll();
        $this->pdoStatement->closeCursor();

        return $result;
    }

    public function queryOne() {
        $this->prepare();

        $this->pdoStatement->execute();
        $result = $this->pdoStatement->fetch();
        $this->pdoStatement->closeCursor();

        return $result;
    }

}