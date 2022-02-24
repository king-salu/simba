<?php
include_once("./classes/connect.php");
include_once("./classes/passwordprotocol.php");
include_once("./classes/uaccount.php");
include("./functions/functions00.php");

/*$_server = "localhost:3307";
$_username = "root";
$_password = "";
$_database = "simba_db";
*/

$_server = "ilzyz0heng1bygi8.chr7pe7iynqr.eu-west-1.rds.amazonaws.com:3306";
$_username = "m8tblwbpbuzeuuq7";
$_password = "sqsgqjs9e3c5ngng";

$_database = "je3ou4murhtmni1n";

$connect = new connect($_server, $_username, $_password, $_database);
echo "connect:: " . $connect->connect_db();
