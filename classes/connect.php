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
        echo "db= $db_inuse <br>";
        //try {
        echo "certified";
        $this->conn = mysqli_connect($this->servername, $this->username, $this->password);
        if (true) {
            echo "... pick a db";
            mysqli_select_db($this->conn, $db_inuse);
            $status = true;
        }

        /*} catch (Exception $ex) {
            echo "Connection failed: " . $ex->getMessage();
        }*/

        return $status;
    }

    public function connect_db_pdo($db = "")
    {
        $status = false;
        $db_inuse = $this->database;
        if (trim($db) != "") $db_inuse = $db;
        try {
            $conn_str = "mysql:host={$this->servername};dbname={$db_inuse};charset=utf8";
            $this->conn = new PDO($conn_str, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $status = true;
        } catch (PDOException $ex) {
            echo "Connection failed: " . $ex->getMessage();
        }

        return $status;
    }

    public function connect_db_sqli($db = "")
    {
        $status = false;
        $db_inuse = $this->database;
        if (trim($db) != "") $db_inuse = $db;
        try {
            $this->conn = new mysqli($this->servername, $this->username, $this->password, $db_inuse);
            $status = true;
            if (mysqli_connect_errno()) {
                echo "Connection failed: " . mysqli_connect_error();
                $status = false;
            }
        } catch (Exception $ex) {
            echo "Connection failed: " . $ex->getMessage();
        }

        return $status;
    }

    public function exec_query_pdo($query)
    {
        $result = array();
        if ($this->connect_db()) {
            try {
                $stmt = $this->conn->query($query);
                $result = $stmt->fetchAll();
            } catch (PDOException $ex) {
                echo "Query failed: " . $ex->getMessage();
            }
        }
        return $result;
    }

    public function exec_query_sqli($query)
    {
        $result = array();
        if ($this->connect_db()) {
            try {
                $stmt = $this->conn->query($query);
                $result = $stmt->fetch_assoc();
            } catch (Exception $ex) {
                echo "Query failed: " . $ex->getMessage();
            }
        }
        return $result;
    }

    public function exec_query($query)
    {
        $result = array();
        echo "about connected... $query <br>";
        $expected = htmlspecialchars($query, ENT_NOQUOTES);
        if ($this->connect_db()) {
            echo " $expected :: connected!";
            try {
                $res = mysqli_query($this->conn, $expected);
                //$result = mysqli_fetch_array($res);
            } catch (Exception $ex) {
                echo "Query failed: " . $ex->getMessage();
            }
        }

        return $result;
    }

    public static function generate_part_query($rdata, $insert = true)
    {
        $query = "";
        $slashes = array('email', 'password');
        if (!empty($rdata)) {
            if ($insert) {
                $_keysslashed = array();
                $query = "(";
                $columns = array_keys($rdata);
                //print_r($rdata); die();
                foreach ($columns as $key => $column) {
                    $query .= "`$column`" . ($key + 1 < count($columns) ? "," : "");
                    //$query .= "'".htmlentities($value,ENT_IGNORE)."'" . ($key + 1 < count($values) ? "," : "");
                    if (in_array($column, $slashes)) $_keysslashed[] = $key;
                }
                $query .= ") VALUES (";

                print_r($_keysslashed);

                $values = array_values($rdata);
                foreach ($values as $key => $value) {
                    echo "$value ::sdget:: " . addslashes($value);
                    $value  = (in_array($key, $_keysslashed)) ? addslashes($value) : $value;
                    $query .= "'$value'" . ($key + 1 < count($values) ? "," : "");
                }

                $query .= ")";
            } else {
                $query = " SET ";
                $index = 0;
                foreach ($rdata as $column => $value) {
                    $value  = (in_array($column, $slashes)) ? addslashes($value) : $value;
                    $query .= " `$column` = '$value' " . ($index + 1 < count($rdata) ? "," : "");
                    $index++;
                }
            }
        }

        return $query;
    }
}
