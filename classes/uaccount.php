<?php
class uaccount extends passwordprotocol
{
    private $usr_acct_id;
    private $message;
    private $pend_savedata = array();
    const DEPOSIT  = 'DEPST00';
    const TRANSFER = 'TRSNF00';

    const DOLLAR   = 'USD';
    const NAIRA    = 'NGN';
    const EURO     = 'EUR';

    const TS_INSUFFICIENTF = 401;
    const TS_UNDEFINED = 200;
    const TS_SUCCESS = 201;

    public function __construct($uacct_id)
    {
        $this->usr_acct_id = $uacct_id;
    }

    public function deposit($value, $currency, $trn_type = uaccount::DEPOSIT)
    {
        global $connect;
        $_status = false;

        if ($value <= 0) {
            $this->message = "deposit amount must be greater than zero";
            return $_status;
        } else if (trim($currency) == "") {
            $this->message = "currency to be deposited in not specified";
            return $_status;
        }

        $valid = $this->validate_id();
        if ($valid) {
            $transc_data = array();
            $transc_data['from_id']    =
                $transc_data['to_id']  = $this->usr_acct_id;
            $transc_data['f_currency']     =
                $transc_data['t_currency'] = $currency;
            $transc_data['curvalue']   = $value;
            $transc_data['trntype']    = $trn_type;
            $transc_data['trnstatus']  = $this::TS_SUCCESS;

            $trn_resp = $this->record_transact($transc_data);
            $_status  = $trn_resp[0];

            if ($_status) {
                $bal_rec = $this->get_balance($currency);
                $save_data = array();

                $dqry = "";
                if (!empty($bal_rec)) {
                    $save_data['balance'] = $bal_rec[0]['balance'] + $value;
                    //$save_data['updated_at'] = date()

                    $dqry0 = "UPDATE `acct_info` SET ";
                    $dqry1 = connect::generate_part_query($save_data, false);
                    $dqry2 = " WHERE (`acct_id`='{$this->usr_acct_id}') ";
                    $dqry  = $dqry0 . $dqry1 . $dqry2;
                } else {
                    $save_data['balance'] = $value;
                    $save_data['acct_id'] = $this->usr_acct_id;
                    $save_data['currency'] = $currency;

                    $dqry0 = "INSERT INTO `acct_info` ";
                    $dqry1 = connect::generate_part_query($save_data);
                    $dqry  = $dqry0 . $dqry1;
                }
                //

                if ($dqry != "") {
                    $connect->exec_query($dqry);
                    $_pend = $this->validate_id(false);
                    if (!$_pend) {
                        if (!empty($this->pend_savedata)) {
                            $_status = save_userinfo($this->usr_acct_id, $this->pend_savedata);
                            if ($_status) {
                                $raw_email = (isset($this->pend_savedata['email'])) ? $this->pend_savedata['email'] : '';
                                $e_mail = $this->unmask_pass($raw_email, 2022, 'D');
                                if (isset($_SESSION['sgp_pend'][$e_mail])) {
                                    unset($_SESSION['sgp_pend'][$e_mail]);
                                }
                                //unset($_COOKIE['SIMBA_PENDU']);
                                setcookie('SIMBA_PENDU', '', time() - 3600, '/');
                                setcookie('SIMBA_logU', '', time() - 3600, '/');
                                $this->pend_savedata = array();
                            } else $this->message = "couldn't create new account";
                        }
                    }
                }
            }
        } else {
            $this->message = "invalid user account";
        }

        return $_status;
    }

    public function transfer($value, $currency, $benefactor, $trn_type = uaccount::TRANSFER)
    {
        global $connect;
        $_status = false;
        $response = array();
        $response['status'] = $_status;
        if (!is_array($benefactor)) {
            $this->message = "invalid beneficiary data";
            return $response;
        }

        if ($value <= 0) {
            $this->message = "transferrable amount must be greater than zero";
            return $response;
        }

        if (trim($currency) == "") {
            $this->message = "currency is not properly set";
            return $response;
        }

        $valid = $this->validate_id(false);
        //echo "very valid $valid {$this->usr_acct_id}";
        if ($valid) {
            if (isset($benefactor['account_id'])) {
                $b_user_id = $benefactor['account_id'];
                if (trim($b_user_id) != "") {
                    $exist_rec = get_userinfo($b_user_id);
                    $_status = (!empty($exist_rec));
                }
            }
            if (!$_status) $this->message = "benefactor's account doesn't exist";

            if (($_status) && (isset($benefactor['currency']))) {
                $_status = (trim($benefactor['currency']) != "");
            }
            if (!$_status) $this->message = "benefactor's currency is not properly set";

            if ($_status) {
                $b_user_id = $benefactor['account_id'];
                $benef_currency = $benefactor['currency'];
                $cur_rate = self::get_api_rate('M30', $currency, $benef_currency);
                $transc_data = array();
                $acct_bal = 0;
                $cur_value = $value / $cur_rate;
                $curaccbalrec = $this->get_balance($currency);
                if (!empty($curaccbalrec)) {
                    $acct_bal = $curaccbalrec[0]['balance'];
                }

                $transc_data['from_id'] = $this->usr_acct_id;
                $transc_data['to_id'] = $b_user_id;
                $transc_data['f_currency'] = $currency;
                $transc_data['t_currency'] = $benef_currency;
                $transc_data['curvalue'] = $value;
                $transc_data['cur_rate'] = $cur_rate;
                $transc_data['trntype'] = $trn_type;

                if ($acct_bal < $cur_value) {
                    $transc_data['trnstatus'] = $this::TS_INSUFFICIENTF;
                    $_status = false;
                } else {
                    $transc_data['trnstatus'] = $this::TS_SUCCESS;
                    $_status = true;
                }

                $this->record_transact($transc_data);
                if ($_status) {
                    $_status = $this->debt_crdt_balance($this->usr_acct_id, $b_user_id, $value, $currency, $benef_currency, $cur_rate);
                }
            }
        }

        return $_status;
    }

    private function record_transact($rdata = array())
    {
        global $connect;
        $_status   = false;
        $_trans_id = "";
        if (!empty($rdata)) {
            $_status = true;
            if ((!isset($rdata['f_currency'])) || (!isset($rdata['t_currency']))) {
                $_status = false;
                $this->message = "all currencies must be properly set";
            }

            if ((!isset($rdata['from_id'])) || (!isset($rdata['to_id']))) {
                $_status = false;
                $this->message = "sender and receiver must be properly set";
            }

            if ((!isset($rdata['cur_rate']))) {
                //$rdata['cur_rate'] = get rate used
            }

            if (!isset($rdata['trnstatus'])) {
                $rdata['trnstatus'] = $this::TS_UNDEFINED;
            }

            $_currency = "";
            if (isset($rdata['f_currency'])) {
                $_currency = $rdata['f_currency'];
            } else if (isset($rdata['t_currency'])) {
                $_currency = $rdata['t_currency'];
            }

            if (!isset($rdata['transc_id'])) {
                $rdata['transc_id'] = $this->gen_transc_id($_currency);
            }

            if ($_status) {
                $_status = false;
                $dqry = "";
                $_trans_id = $rdata['transc_id'];
                $exist_rec = $this->get_transac_dets($_trans_id);
                if (empty($exist_rec)) {
                    $dqry0 = "INSERT INTO `transactions` ";
                    $dqry1 = connect::generate_part_query($rdata);
                    $dqry  = $dqry0 . $dqry1;
                } else {
                    $dqry0 = "UPDATE `transactions` SET ";
                    $dqry1 = connect::generate_part_query($rdata, false);
                    $dqry2 = " WHERE (`transc_id` = '$_trans_id') ";
                    $dqry  = $dqry0 . $dqry1 . $dqry2;
                }

                if ($dqry != "") {
                    $connect->exec_query($dqry);
                    $_status = true;
                }
            }
        }

        return array($_status, $_trans_id);
    }

    private function debt_crdt_balance($from_id, $to_id, $value, $fcurrency, $tcurrency, $cur_rate)
    {
        global $connect;
        $_status = false;
        if ((trim($from_id) == "") || (trim($to_id) == "") || (trim($value) == "") || (trim($fcurrency) == "")
            || (trim($tcurrency) == "") || (trim($cur_rate) == "")
        ) {
            $this->message = "sender, receiver, value, rate or currency can't be empty";
            return $_status;
        }

        $uacc_dbt = new uaccount($from_id);
        $uacc_crdt = new uaccount($to_id);

        if ($uacc_crdt->validate_id(false) && $uacc_dbt->validate_id(false)) {
            $acc_bal_recs = $uacc_dbt->get_balance($fcurrency);
            $acc_bal_dbt = (!empty($acc_bal_recs)) ? $acc_bal_recs[0]['balance'] : 0;
            if ($value <= $acc_bal_dbt) {
                $acc_bal_dbt -= $value;
                $dqry = "UPDATE `acct_info` SET `balance`='$acc_bal_dbt' WHERE (`acct_id`='$from_id') and (`currency`='$fcurrency')";
                $connect->exec_query($dqry);

                $acc_bal_recs = $uacc_crdt->get_balance($tcurrency);
                $acc_bal_crdt = (!empty($acc_bal_recs)) ? $acc_bal_recs[0]['balance'] : 0;
                if (empty($acc_bal_recs)) {
                    //create account info for new currency
                    $acct_data = array();
                    $acct_data['acct_id'] = $to_id;
                    $acct_data['balance'] = 0;
                    $acct_data['currency'] = $tcurrency;
                    $dqry = "INSERT INTO `acct_info` ";
                    $dqry0 = connect::generate_part_query($acct_data);

                    $dqry = $dqry . $dqry0;
                    $connect->exec_query($dqry);
                }

                $cur_value = $value * $cur_rate;
                $acc_bal_crdt += $cur_value;
                $dqry = "UPDATE `acct_info` SET `balance`='$acc_bal_crdt' WHERE (`acct_id`='$to_id') and (`currency`='$tcurrency')";
                $connect->exec_query($dqry);

                $_status = true;
            } else $this->message = "value to be debited is more than account balance";
        }

        return $_status;
    }

    private function gen_transc_id($currency = '')
    {
        global $connect;

        $currency = (strlen($currency) < 3) ? 'ACC' : strtoupper($currency);
        $stg1 = substr($currency, 0, 3);

        $yr = date('Y');
        $hlfbr0 = strlen($yr) / 2;
        $hlfbr0 = intval($hlfbr0);
        $hlfbr1 = strlen($yr) - $hlfbr0;
        $stg2 = substr($yr, 0, $hlfbr0);
        $stg3 = substr($yr, $hlfbr0, $hlfbr1);

        $stg0 = date('m');
        $stg4 = date('d');
        $stg5 = 0;
        $search = true;
        $gen_code = "";

        while ($search) {
            $gen_code = "S{$stg0}{$stg1}{$stg2}A{$stg3}L{$stg4}U{$stg5}";
            $exist_rec = $this->get_transac_dets($gen_code);
            if (empty($exist_rec)) $search = false;
            $stg5++;
        }

        return $gen_code;
    }

    public static function get_transac_dets($trans_id, $filters = array())
    {
        global $connect;
        $dqry0 = (trim($trans_id) != "") ? " and (`transc_id`='$trans_id') " : "";

        $dqry1 =  (isset($filters['sender'])) ?  " and (`from_id` = '{$filters['sender']}') " : "";

        $dqry2 = (isset($filters['receiver'])) ?  " and (`to_id` = '{$filters['receiver']}') " : "";

        $dqry3 = (isset($filters['curr_sent'])) ? " and (`f_currency` = '{$filters['curr_sent']}') " : "";

        $dqry4 = (isset($filters['curr_receive'])) ? " and (`t_currency` = '{$filters['curr_receive']}') " : "";

        $dqry = "SELECT `transc_id`, `from_id`, `to_id`, `f_currency`, `t_currency`, `curvalue`, 
                        `cur_rate`, `trntype`, `created_at`, `updated_at` 
                FROM `transactions` WHERE (1=1) ";

        $dqry = $dqry . $dqry0 . $dqry1 . $dqry2 . $dqry3 . $dqry4;


        $trnsc_recs = $connect->exec_query($dqry);

        return $trnsc_recs;
    }

    private function validate_id($external = true)
    {
        $_status = false;
        $acc_rec = get_userinfo($this->usr_acct_id);
        $_status = (!empty($acc_rec));
        if ((!$_status) && ($external)) {
            if (isset($_COOKIE['SIMBA_PENDU'])) {
                //$PIP = new passwordprotocol($this->usr_acct_id);
                $email_address = $this->unmask_pass($_COOKIE['SIMBA_PENDU'], 2022, 'D');
                if (session_status() == PHP_SESSION_NONE) session_start();
                //print_r($_SESSION['sgp_pend']);
                if (isset($_SESSION['sgp_pend'][$email_address])) {
                    $temp_data = $_SESSION['sgp_pend'][$email_address];
                    if (isset($temp_data['user_id'])) {
                        $_status = ($temp_data['user_id'] === $this->usr_acct_id);
                        if ($_status) {
                            $this->pend_savedata = $temp_data;
                        }
                    }
                }
            }
        }
        return $_status;
    }

    public static function get_code_desc($code, $type, $r_default = true)
    {
        $desc = ($r_default) ? $code : "";
        switch (strtolower($type)) {
            case 'transaction_type':
                switch ($code) {
                    case uaccount::DEPOSIT:
                        $desc = 'DEPOSIT';
                        break;
                    case uaccount::TRANSFER:
                        $desc = 'TRANSFER';
                        break;
                }
                break;

            case 'currency':
                switch ($code) {
                    case uaccount::NAIRA:
                        $desc = 'NAIRA';
                        break;
                    case uaccount::DOLLAR:
                        $desc = 'DOLLAR';
                        break;
                    case uaccount::EURO:
                        $desc = 'EURO';
                        break;
                }
                break;

            case 'currency_symbol':
                switch ($code) {
                    case uaccount::NAIRA:
                        $desc = '₦';
                        break;
                    case uaccount::DOLLAR:
                        $desc = '$';
                        break;
                    case uaccount::EURO:
                        $desc = '€';
                        break;
                }
                break;

            case 'transaction_status':
                switch ($code) {
                    case uaccount::TS_SUCCESS:
                    case uaccount::TS_UNDEFINED:
                        $desc = 'success';
                        break;
                    case uaccount::TS_INSUFFICIENTF:
                        $desc = 'insufficient funds';
                        break;
                }
                break;
        }

        return $desc;
    }

    public function get_balance($currency)
    {
        global $connect;

        $dqry0 = (trim($currency) != "") ? " and (`currency` = '$currency') " : "";

        $dqry = "SELECT `balance`, `created_at`, `updated_at`, `currency` FROM `acct_info` WHERE (`acct_id` = '{$this->usr_acct_id}') ";

        $dqry = $dqry . $dqry0;

        $result = $connect->exec_query($dqry);

        foreach ($result as $_key => $balr) {
            $balance = $this->get_code_desc($balr['currency'], 'currency_symbol', false) . number_format($balr['balance'], 2);
            $result[$_key]['currency_desc'] = $this->get_code_desc($balr['currency'], 'currency');
            $result[$_key]['bal_display']   = $balance;
        }

        return $result;
    }

    public static function get_api_rate($api_type, $from_currency, $to_currency)
    {
        $rate = 1;
        switch ($api_type) {
            case "M30":
                $api_token = "NDEwYjM0MTktMmMyYi00NjljLTlkM2EtMDFkZDE3ZjVlNzNk"; //"NzhhZmU3ZDUtOTM3ZS00MzQ2LWIzZjMtNzI5NjRhZTQ0Mzg5";
                $api_bearer = "simba_tset";
                $curl_url  = "https://api.m3o.com/v1/currency/Convert";

                //text/plain", //application/json
                $curl_headers = array(
                    "Authorization: Bearer $api_token"
                );
                $post_headers = array(
                    "from" => $from_currency,
                    "to" => $to_currency
                );

                //print_r($post_headers);

                $curl_connect = curl_init();
                curl_setopt_array($curl_connect, array(
                    CURLOPT_URL => $curl_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $post_headers,
                    CURLOPT_HTTPHEADER => $curl_headers
                ));

                $curl_response = curl_exec($curl_connect);
                $res = json_decode($curl_response, true);
                //var_dump($curl_connect);
                //print_r($curl_response);
                //echo "spot:  :: " . curl_error($curl_connect);
                //print_r($res);
                if (!empty($res)) {
                    $rate = $res['rate'];
                }
                curl_close($curl_connect);
                break;
        }

        return $rate;
    }

    public function get_feedback()
    {
        return $this->message;
    }


    public function get_report($filters = array())
    {
        global $connect;

        $acct_from = (isset($filters['acct_from'])) ? $filters['acct_from'] : '';
        $acct_to = (isset($filters['acct_to'])) ? $filters['acct_to'] : '';
        $src_currency = (isset($filters['src_currency'])) ? $filters['src_currency'] : '';
        $targ_currency = (isset($filters['targ_currency'])) ? $filters['targ_currency'] : '';
        $transc_status = (isset($filters['transc_status'])) ? $filters['transc_status'] : '';
        $transc_type = (isset($filters['transc_type'])) ? $filters['transc_type'] : '';

        $dqry0 = " and ((`from_id` = '{$this->usr_acct_id}') or (`to_id` = '{$this->usr_acct_id}')) ";

        if (trim($acct_from) != '') {
            $dqry0 = " and (`from_id` = '$acct_from') and (`to_id` = '{$this->usr_acct_id}') ";
        } else if (trim($acct_to) != '') {
            $dqry0 = " and (`from_id` = '{$this->usr_acct_id}') and (`to_id` = '$acct_to') ";
        }

        $dqry1 = (trim($src_currency) != '') ? " and (`f_currency` = '$src_currency') " : "";

        $dqry2 = (trim($targ_currency) != '') ? " and (`t_currency` = '$targ_currency') " : "";

        $dqry3 = (trim($transc_status) != '') ? " and (`trnstatus` = '$transc_status') " : "";

        $dqry4 = (trim($transc_type) != '') ? " and (`trntype` = '$transc_type') " : "";

        $dqry = "SELECT * FROM `transactions` WHERE (1=1) ";

        $d_orderby = " order by created_at DESC ";

        $dqry = $dqry . $dqry0 . $dqry1 . $dqry2 . $dqry3 . $dqry4 . $d_orderby;

        $report = array();

        $transc_recs = $connect->exec_query($dqry);
        if (!empty($transc_recs)) {
            foreach ($transc_recs as $trn_rec) {
                $from_id      = $trn_rec['from_id'];
                $to_id        = $trn_rec['to_id'];
                $f_currency   = $trn_rec['f_currency'];
                $t_currency   = $trn_rec['t_currency'];
                $trntype      = $trn_rec['trntype'];
                $trnstatus    = $trn_rec['trnstatus'];
                $cur_rate     = $trn_rec['cur_rate'];
                $curvalue     = abs($trn_rec['curvalue']);
                $crtd_at      = $trn_rec['created_at'];
                $updtd_at     = $trn_rec['updated_at'];

                $f_acc_det    = (trim($from_id) != "") ? get_userinfo($from_id) : array();
                $f_fullname   = (!empty($f_acc_det)) ? $f_acc_det[0]['fullname'] : $from_id;

                $t_acc_det    = (trim($to_id) != "") ? get_userinfo($to_id) : array();
                $t_fullname   = (!empty($t_acc_det)) ? $t_acc_det[0]['fullname'] : $to_id;

                $f_curr_desc  = $this::get_code_desc($f_currency, 'currency_symbol', false);
                $f_curr_desc  = (($f_curr_desc != '') ? "($f_curr_desc) " : "") . $f_currency;

                $t_curr_desc  = $this::get_code_desc($t_currency, 'currency_symbol', false);
                $t_curr_desc  = (($t_curr_desc != '') ? "($t_curr_desc) " : "") . $t_currency;

                $curval_display = "";
                $targ_currency  = "";
                if ($to_id == $this->usr_acct_id) {
                    $t_fullname = "You";
                    if ($trntype == $this::DEPOSIT) $f_fullname = "";
                    $targ_currency = $t_curr_desc;
                    $curvalue = $curvalue * $cur_rate;
                    $_curvalue = number_format($curvalue, 2);
                    $curval_display = "<font style='color: green;'>+{$_curvalue}</font>";
                } else if ($from_id == $this->usr_acct_id) {
                    $f_fullname = "You";
                    $targ_currency = $f_curr_desc;
                    $curvalue = -1 * $curvalue;
                    $_curvalue = number_format($curvalue, 2);
                    $curval_display = "<font style='color: red;'>{$_curvalue}</font>";
                }

                $trn_rec['from_name']       = $f_fullname;
                $trn_rec['to_name']         = $t_fullname;
                $trn_rec['f_currency_desc'] = $f_curr_desc;
                $trn_rec['t_currency_desc'] = $t_curr_desc;
                $trn_rec['targ_currency']   = $targ_currency;
                $trn_rec['curvalue2']       = $curvalue;
                $trn_rec['curval_display']  = $curval_display;
                $trn_rec['createdat_desc']  = date('F d, Y H:i', strtotime($crtd_at));
                $trn_rec['updatedat_desc']  = date('F d, Y H:i', strtotime($updtd_at));
                $trn_rec['trntype_desc']    = $this::get_code_desc($trntype, 'transaction_type');
                $trn_rec['trnstatus_desc']  = $this::get_code_desc($trnstatus, 'transaction_status');

                $report[] = $trn_rec;
            }
        }

        return $report;
    }

    public function get_acct_info()
    {
        $personal_data = array();

        $user_info = get_userinfo($this->usr_acct_id);
        if (!empty($user_info)) {
            $email_raw = $user_info[0]['email'];

            $personal_data['last_name'] = $user_info[0]['last_name'];
            $personal_data['first_name'] = $user_info[0]['first_name'];
            $personal_data['fullname'] = $user_info[0]['fullname'];
            $personal_data['email'] = $this->unmask_pass($email_raw, 2022, 'D');
        }

        return $personal_data;
    }
}
