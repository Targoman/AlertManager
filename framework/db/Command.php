<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

namespace Targoman\AlertManager\framework\db;

class Command {
    public $db;
    public $qry;
    public $params=[];
    public $pdoStatement;

    public function __construct($_db, $_qry, $_params=[]) {
        if (empty($_qry))
            throw new \Exception("Query string is empty");

        $this->db = $_db;
        $this->qry = $_qry;
        $this->params = $_params;
    }

    public function prepare() {
        if ($this->pdoStatement)
            return;

        $this->pdoStatement = $this->db->getPDO()->prepare($this->qry);

        if (empty($this->params) == false) {
            foreach ($this->params as $k => $v) {
                if (is_array($v))
                    $this->pdoStatement->bindValue($k, implode(',', $v));
                else {
                    $paramType = \PDO::PARAM_STR;

                    if (is_numeric($v))
                        $paramType = \PDO::PARAM_INT;
                    // elseif ...

                    $this->pdoStatement->bindValue($k, $v, $paramType);
                }
            }
        }
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

    public function execute() {
        $this->prepare();

        $this->pdoStatement->execute();
        $result = $this->pdoStatement->rowCount();

        return $result;
    }

}
