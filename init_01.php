<?php
include_once("./classes/connect.php");
include_once("./classes/passwordprotocol.php");
include_once("./classes/uaccount.php");
include("./functions/functions00.php");

$_server = "localhost:3307";
$_username = "root";
$_password = "";
$_database = "simba_db";

$connect = new connect($_server, $_username, $_password, $_database);
//echo "connect:: " . $connect->connect_db();
