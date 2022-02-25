<?php
include_once('./init_01.php');


function get_userinfo($_userid = '', $_email = '')
{
    global $connect;
    $dqry0 = "SELECT `user_id`, `first_name`, `last_name`, `email`, `created_at`, `updated_at` 
             FROM `user_info` WHERE (1=1) ";

    $dqry1 = "";
    if (trim($_userid) != '') {
        $dqry1 = " and (user_id = '$_userid') ";
    }

    $dqry2 = "";
    $mail_addr = "";
    if (trim($_email) != '') {
        $PIP = new passwordprotocol('evolve');
        $mail_addr = $PIP->evolve($_email);
        //echo "mail address:$mail_addr <br>";
        $mail_encde = utf8_decode($mail_addr);
        //$dqry2 = ' and (email = "' . $mail_addr . '") ';
        //$dqry2 = ' and (email = "' . $mail_encde . '") ';
        //$dqry2 = ' and (email = "hello") ';
    }

    $dqry = $dqry0 . $dqry1 . $dqry2;

    //echo "query2: $dqry ";
    //die();
    $user_dets = $connect->exec_nquery($dqry);

    $users_set = array();
    if (!empty($user_dets)) {
        foreach ($user_dets as $_key => $user) {
            $user['fullname'] = "{$user['last_name']} {$user['first_name']}";
            $incomingmail = $user['email'];
            $valid = false;
            echo "equality:: ($incomingmail == $mail_addr) <br>";
            $step1 = utf8_encode($incomingmail);
            $PIP = new passwordprotocol('evolve');
            $clearmail = $PIP->evolve2($step1);
            //$clearmail = $PIP->evolve2($incomingmail);
            echo "Clear Mail: $clearmail <br>";
            /* if (($mail_addr != "") && ($incomingmail === $mail_addr)) {
                $valid = true;
            } else $valid = true;

            if ($valid)*/
            $users_set[] = $user;
        }
    }

    return $users_set;
}

function save_userinfo($_userid, $rdata = array())
{
    global $connect;
    //print_r($rdata);
    $_status = false;
    $exists = get_userinfo($_userid);
    $insert = (empty($exists)) ? true : false;
    $dqry = "";
    if ($insert) {
        $dqry0 = "INSERT INTO `user_info` ";
        $dqry1 = connect::generate_part_query($rdata);

        $dqry = $dqry0 . $dqry1;
    } else {
        $dqry0 = "UPDATE `user_info` ";
        $dqry1 = connect::generate_part_query($rdata, false);
        $dqry2 = " WHERE (`user_id`='$_userid') ";

        $dqry = $dqry0 . $dqry1 . $dqry2;
    }

    if ($dqry != "") {
        echo $dqry; //die();
        $connect->exec_query($dqry);
        $_status = true;
    }

    return $_status;
}

function validate_processdata($action, $rdata = array(), $stage = 0)
{
    $_status = false;
    $action = strtolower($action);
    $error = "";
    $step = -1;

    switch ($action) {
        case "login":
            $_status = true;
            $access = (isset($rdata['access'])) ? $rdata['access'] : '';
            if (trim($access) == "") {
                $_status = false;
                $error = "invalid email address";
            }
            break;

        case "signup":
            $_status = true;

            $fname = (isset($rdata['fname'])) ? $rdata['fname'] : '';
            $lname = (isset($rdata['lname'])) ? $rdata['lname'] : '';
            $email = (isset($rdata['access'])) ? $rdata['access'] : '';
            $pass1 = (isset($rdata['pass1'])) ? $rdata['pass1'] : '';
            $pass2 = (isset($rdata['pass2'])) ? $rdata['pass2'] : '';

            if (($_status) && (trim($fname) == "")) {
                $_status = false;
                $error = "First name field can't be empty";
                $step = 0;
            }

            if (($_status) && (trim($lname) == "")) {
                $_status = false;
                $error = "Last name field can't be empty";
                $step = 1;
            }

            if (($_status) && (trim($email) == "")) {
                $_status = false;
                $error = "email field can't be empty";
                $step = 2;
            } else if ($_status) {
                $exists = get_userinfo('', $email);
                //print_r($exists);
                //die();
                if (!empty($exists)) {
                    $_status = false;
                    $error = "email already exist! email must be Unique";
                    $step = 2;
                }
            }

            if (($_status) && ($pass1 !== $pass2)) {
                $_status = false;
                $error = "password mismatch";
                $step = 3;
            } else if (($_status) && ((strlen($pass1) < 8) || (strlen($pass1) > 30))) {
                $_status = false;
                $error = "password must be greater than 8 characters";
                $step = 3;
            }

            break;

        case "deposit":
            $_status = true;

            $cur_value = (isset($rdata['cur_value'])) ? $rdata['cur_value'] : 0;
            $targ_cur = (isset($rdata['targ_cur'])) ? $rdata['targ_cur'] : "";
            if (($_status) && ($cur_value < 1000)) {
                $_status = false;
                $error = "deposit amount must not be less than 1000";
            }

            if (($_status) && (trim($targ_cur) == "")) {
                $_status = false;
                $error = "currency to be deposited in must be set properly";
            }

            if ($_status) {
                $acct_from = "";
                if (isset($rdata['acct_from'])) {
                    $acct_from = $rdata['acct_from'];
                }

                if (trim($acct_from) != "") {
                    $exist_rec = get_userinfo($acct_from);
                    $_status = (!empty($exist_rec));
                } else if (isset($_COOKIE['SIMBA_PENDU'])) {
                    $PIP = new passwordprotocol('sample');
                    $pend_email = $PIP->unmask_globals('COOKIE', 'SIMBA_PENDU');
                    if (session_status() == PHP_SESSION_NONE) session_start();
                    if (isset($_SESSION['sgp_pend'][$pend_email])) {
                        $pend_data = $_SESSION['sgp_pend'][$pend_email];
                        //print_r($pend_data);
                        if (isset($pend_data['user_id'])) {
                            $_status = true;
                            $_REQUEST['acct_from'] = $pend_data['user_id'];
                        }
                    }
                }

                if (!$_status) $error = "account to deposit is empty or doesn't exist";
            }

            break;

        case "transfer":
            $_status = true;

            $src_cur = (isset($rdata['src_cur'])) ? $rdata['src_cur'] : '';
            if ($src_cur == "") {
                $_status = false;
                $error = "currency to be debited from must be set properly";
            }

            $targ_cur = (isset($rdata['targ_cur'])) ? $rdata['targ_cur'] : '';
            if (($_status) && ($targ_cur == "")) {
                $_status = false;
                $error = "beneficiary currency account must be set properly";
            }

            if ($_status) {
                $acct_to = (isset($rdata['acct_to'])) ? $rdata['acct_to'] : '';
                if (trim($acct_to) != "") {
                    $exist_rec = get_userinfo($acct_to);
                    $_status = (!empty($exist_rec));
                }

                $acct_from = (isset($rdata['acct_from'])) ? $rdata['acct_from'] : '';

                if (($_status) && (trim($acct_from) != "")) {
                    $exist_rec = get_userinfo($acct_from);
                    $_status = (!empty($exist_rec));
                }

                if ($_status) $error = "beneficiary account is empty or invalid";
            }

            break;
    }

    return array($_status, $error, $step);
}


function gen_user_id()
{
    global $connect;
    $ydate = date('Y');
    $stg1 = "";
    for ($i = strlen($ydate) - 1; $i >= 0; $i--) {
        $stg1 .= $ydate[$i];
    }

    $stg2 = date('s');
    $stg3 = 0;
    $search = true;
    $userid_gen = "";
    while ($search) {
        $userid_gen = "USR{$stg2}{$stg1}S{$stg3}";
        $exists = get_userinfo($userid_gen);
        if (empty($exists)) $search = false;
        $stg3++;
    }

    return $userid_gen;
}
