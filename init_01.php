<?php
error_reporting(E_ALL);
ini_set('display_errors',true);

//header('Content-Type: text/plain; charset=utf-8');

include_once("./classes/connect.php");
include_once("./classes/passwordprotocol.php");
include_once("./classes/uaccount.php");
include("./functions/functions00.php");

/*$_server = "localhost:3307";
$_username = "root";
$_password = "";
$_database = "simba_db";
*/

//require "vendor/ait"
//$uurl = 'mysql://m8tblwbpbuzeuuq7:sqsgqjs9e3c5ngng@ilzyz0heng1bygi8.chr7pe7iynqr.eu-west-1.rds.amazonaws.com:3306/je3ou4murhtmni1n';
$uurl = "mysql://m645bjovj4jgx7c0:ul3c5ywzkm7e128p@ilzyz0heng1bygi8.chr7pe7iynqr.eu-west-1.rds.amazonaws.com:3306/xw33xn96e8tl958g";
//$uurl = 'JAWSDB_URL';
//file_put_contents("php://stderr", "something happened!");
$env_var = getenv($uurl);
//$env_var = $_SERVER[$uurl];
echo "<pre> set";
var_dump($env_var);
echo "</pre>";

$_server = "ilzyz0heng1bygi8.chr7pe7iynqr.eu-west-1.rds.amazonaws.com:3306";
$_username = "m8tblwbpbuzeuuq7";
$_password = "sqsgqjs9e3c5ngng";

$_database = "je3ou4murhtmni1n";
error_log("hello, this is a test!");
//echo "host:$_server <br> ";
$connect = new connect($_server, $_username, $_password, $_database);
echo "connect:: " . $connect->connect_db();
$connect->exec_query("CREATE TABLE IF NOT EXISTS `activity_record` (
    `type` int(11) NOT NULL,
    `activity` varchar(50) NOT NULL,
    `act_mode` int(11) NOT NULL,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `member_id` int(11) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
?>
<h1>hello world</h1>