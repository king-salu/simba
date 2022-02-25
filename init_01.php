<?php
//header("content-type: text/html; charset=ISO-8859-1");
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

$_server = "ilzyz0heng1bygi8.chr7pe7iynqr.eu-west-1.rds.amazonaws.com:3306";
$_username = "m8tblwbpbuzeuuq7";
$_password = "sqsgqjs9e3c5ngng";

$_database = "je3ou4murhtmni1n";
//echo "host:$_server <br> ";
$connect = new connect($_server, $_username, $_password, $_database);
//echo "connect:: " . $connect->connect_db();
$userid = 'sample001';
$_email = 'simbatest40@gmail.com';
$_pass  = 'simbatest40';
$PIP = new passwordprotocol($userid);
$mail_e = $PIP->evolve($_email);
$pass_e = $PIP->evolve($_pass);  
$save_data = array();
$save_data['first_name'] = 'Tunde';
$save_data['last_name'] = 'Onakoya';
$save_data['email'] = $mail_e;
$save_data['password'] = $pass_e;

save_userinfo($userid,$save_data);
  
