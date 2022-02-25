<?php
class connect
{
    private $conn = NULL;
    private $servername = "";
    private $username = "";
    private $password = "";
    private $database = "";

    public function __construct($server, $user, $pass, $db = "")
    {
        $this->servername = $server;
        $this->username = $user;
        $this->password = $pass;
        $this->database = $db;
    }

    public function connect_db($db = "")
    {
        $status = false;
        $db_inuse = $this->database;
        if (trim($db) != "") $db_inuse = $db;
        try {
            $conn_str = "mysql:host={$this->servername};dbname={$db_inuse}";
            $this->conn = new PDO($conn_str, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $status = true;
        } catch (PDOException $ex) {
            echo "Connection failed: " . $ex->getMessage();
        }

        return $status;
    }

    public function exec_query($query)
    {
        $result = array();
        if ($this->connect_db()) {
            try{
            $stmt = $this->conn->query($query);
            $result = $stmt->fetchAll();
            }
            catch(PDOException $ex){
                echo "Query failed: " . $ex->getMessage();
            }
        }
        return $result;
    }

    public static function generate_part_query($rdata, $insert = true)
    {
        $query = "";
        if (!empty($rdata)) {
            if ($insert) {
                $query = "(";
                $columns = array_keys($rdata);
                foreach ($columns as $key => $column) {
                    $query .= "`$column`" . ($key + 1 < count($columns) ? "," : "");
                }
                $query .= ") VALUES (";


                $values = array_values($rdata);
                foreach ($values as $key => $value) {
                    $query .= "'$value'" . ($key + 1 < count($values) ? "," : "");
                }

                $query .= ")";
            } else {
                $query = " SET ";
                $index = 0;
                foreach ($rdata as $column => $value) {
                    $query .= " `$column` = '$value' " . ($index + 1 < count($rdata) ? "," : "");
                    $index++;
                }
            }
        }

        return $query;
    }
}
