<?php
include_once("init_01.php");
//samp=array('A','B','C');
//echo "<pre>";
//print_r($_REQUEST);
//print_r($_POST);
//echo "</pre>";
if (isset($_REQUEST['action'])) {
    $req_action = $_REQUEST['action'];
    $response = array();
    $action_status = false;
    switch (strtolower($req_action)) {
        case "login":
            $validatei = validate_processdata($req_action, $_REQUEST);
            if ($validatei[0]) {
                $req_email = (isset($_REQUEST['access'])) ? $_REQUEST['access'] : '';
                $req_password = (isset($_REQUEST['password'])) ? $_REQUEST['password'] : '';

                $user_dets = get_userinfo('', $req_email);
                if (!empty($user_dets)) {
                    $user_id_inuse = $user_dets[0]['user_id'];
                    $PIP = new passwordprotocol($user_id_inuse);
                    $action_status = $PIP->validate_password($req_password);
                    $_REQUEST['key_input'] = $user_id_inuse;
                    Page_SessionStats(4);
                }
            }

            if (!$action_status) {
                $response['message'] = 'invalid login details, please re-check credentials';
                $response['e_step'] = $validatei[2];
            }
            break;

        case "signup":
            $validatei = validate_processdata($req_action, $_REQUEST);
            print_r($validatei);
            die();
            if ($validatei[0]) {
                $req_fname = (isset($_REQUEST['fname'])) ? $_REQUEST['fname'] : '';
                $req_lname = (isset($_REQUEST['lname'])) ? $_REQUEST['lname'] : '';
                $req_email = (isset($_REQUEST['email'])) ? $_REQUEST['email'] : '';
                $req_pass1 = (isset($_REQUEST['pass1'])) ? $_REQUEST['pass1'] : '';

                $new_user_id = gen_user_id();
                $PIP = new passwordprotocol($new_user_id);
                $password = $PIP->evolve($req_pass1);
                $email_address = $PIP->evolve($req_email);

                $save_data = array();
                $save_data['first_name'] = $req_fname;
                $save_data['last_name'] = $req_lname;
                $save_data['email'] = $email_address;
                $save_data['password'] = $password;
                $save_data['user_id'] = $new_user_id;

                $_REQUEST['key_input'] = $email_address;
                Page_SessionStats(1);
                $_SESSION['sgp_pend'][$req_email] = $save_data;
                $action_status = true; //save_userinfo($new_user_id, $save_data);
            } else {
                $response['message'] = $validatei[1];
                $response['e_step'] = $validatei[2];
            }
            break;

        case "deposit":
            $validatei = validate_processdata($req_action, $_REQUEST);
            if ($validatei[0]) {
                //print_r($_REQUEST);
                $req_acctfrom_id = (isset($_REQUEST['acct_from'])) ? $_REQUEST['acct_from'] : '';
                $req_src_cur = (isset($_REQUEST['src_cur'])) ? $_REQUEST['src_cur'] : '';
                $req_cur_value = (isset($_REQUEST['cur_value'])) ? $_REQUEST['cur_value'] : '';

                $depo_acct = new uaccount($req_acctfrom_id);
                $action_status = $depo_acct->deposit($req_cur_value, $req_src_cur);

                $validatei[1] = $depo_acct->get_feedback();
                //$rate = $depo_acct->get_api_rate('M30', 'USD', 'EUR');
                //echo "status: $action_status , " . $depo_acct->get_feedback();
            }

            if (!$action_status) {
                $response['message'] = $validatei[1];
            }
            break;

        case "transfer":
            $usr_logged = getLoggedID();
            if ($usr_logged != "ID") {
                $_REQUEST['acct_from'] = $usr_logged;
                $validatei = validate_processdata($req_action, $_REQUEST);
                if ($validatei[0]) {
                    $req_acctfrom_id = (isset($_REQUEST['acct_from'])) ? $_REQUEST['acct_from'] : '';
                    $req_src_cur = (isset($_REQUEST['src_cur'])) ? $_REQUEST['src_cur'] : '';
                    $req_cur_value = (isset($_REQUEST['cur_value'])) ? $_REQUEST['cur_value'] : '';
                    $req_benefactor = array();
                    $req_benefactor['account_id'] = (isset($_REQUEST['acct_to'])) ? $_REQUEST['acct_to'] : '';
                    $req_benefactor['currency'] = (isset($_REQUEST['targ_cur'])) ? $_REQUEST['targ_cur'] : '';
                    $transf_acct = new uaccount($usr_logged);
                    $action_status = $transf_acct->transfer($req_cur_value, $req_src_cur, $req_benefactor);
                    $validatei[1] = $transf_acct->get_feedback();
                }
            }

            if (!$action_status) {
                $response['message'] = $validatei[1];
            }
            break;
    }

    $response['status'] = $action_status;

    echo json_encode($response);
}


function Page_SessionStats($mode)
{
    switch ($mode) {
        case 0:
            $user_logged = getLoggedID();
            if ($user_logged == "ID") {
                header("Location: ./login.html");
            }
            break;

        case 1:
            if (session_status() == PHP_SESSION_NONE) session_start();
            if (isset($_REQUEST["key_input"])) {
                setcookie("SIMBA_logU", '', time() * -1, "/");
                setcookie("SIMBA_PENDU", $_REQUEST["key_input"], time() + 86400, "/");
            }
            break;

        case 4:
            if (session_status() == PHP_SESSION_NONE) session_start();
            if (isset($_SESSION["SIMBA_logU"]))
                unset($_SESSION["SIMBA_logU"]);

            if (isset($_REQUEST["key_input"])) {
                $duration = 1;
                $values = array($_REQUEST["key_input"], microtime(true), 0, Date('Y-m-d H:i:s', strtotime("+$duration days")));
                //print_r($values);
                if (getLoggedID() != "ID") unset($_COOKIE["SIMBA_logU"]);
                setcookie("SIMBA_logU", json_encode($values), time() + 86400 * $duration, "/");
                //var_dump($_COOKIE);
            }
            break;
    }
}

function getLoggedID($full = false, $extra = 'details')
{
    $input = "ID";
    if (isset($_COOKIE["SIMBA_logU"])) {
        $input = json_decode($_COOKIE["SIMBA_logU"], true);
        if (!$full)
            $input = $input[0];
        else {
            $result = array('cookie' => $input);
            switch ($extra) {
                case 'details':
                    $micro = sprintf("%06d", ($input[1] - floor($input[1])) * 1000000);
                    $startDate = (new DateTime(date('Y-m-d H:i:s.' . $micro, $input[1])));
                    $startDate = $startDate->format('Y-m-d H:i:s');

                    $result['startdate'] = $startDate;
                    $result['enddate'] = $input[3];

                    $startDate = strtotime($startDate);
                    $enddate = strtotime($input[3]);
                    $days = round(($enddate - $startDate) / (60 * 60 * 24));

                    $result['days'] = $days;
                    $result['remember'] = ($days >= 365) ? true : false;

                    return $result;
                    break;
            }
        }
    }


    return $input;
}
