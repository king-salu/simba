<?php
class passwordprotocol
{
    private $user_access = NULL;
    function __construct($user_id)
    {
        $this->user_access = $user_id;
    }

    public function validate_password($curpass)
    {
        $state = false;
        $exist_pass = $this->get_curpass('D');


        echo "<br>pass ($curpass === $exist_pass)";
        if ($curpass === $exist_pass) {
            $state = true;
        }

        return $state;
    }

    protected function unmask_pass($prevcode, $rand, $op)
    {
        $newcode = "";

        if (($prevcode == "") || ($op == "") || ($rand == "")) {
            return "";
        }

        //Encode
        if ($op == "E") { //Encode 
            $move = 0;
            $fill = chr($rand);
            $move = $rand - 65;
            $newcode = "";
            $cnt   = strlen($prevcode);
            for ($c1 = 0; $c1 < $cnt; $c1++) {
                $subs = substr($prevcode, $c1, 1);
                $cval = ord($subs) - $move;
                $newcode = $newcode . chr($cval) . $fill;
            }
        } else if ($op == "D") { //Decode
            $move = $rand - 65;
            $newcode = "";
            $cnt   = strlen($prevcode);

            $alter = true;
            for ($c1 = 0; $c1 < $cnt; $c1++) {
                $subs = substr($prevcode, $c1, 1);

                $cval = ord($subs) + $move;
                if ($alter) {
                    $newcode = $newcode . chr($cval);
                    $alter   = false;
                } else {
                    $alter = true;
                }
            }
        }

        return $newcode;
    }

    private function get_curpass($op = 'E')
    {
        global $connect;
        $user_pass = '';

        $dqry = "SELECT `password` FROM `user_info` WHERE user_id = '{$this->user_access}'";

        //echo "dqry:: $dqry";
        $udata = $connect->exec_fquery($dqry);
        if (!empty($udata)) {
            $cpasswrd = $udata[0]['password'];
            //echo "password :" . $cpasswrd;
            $user_pass = $this->unmask_pass($cpasswrd, 2022, $op);
        }

        return $user_pass;
    }

    public function evolve($celpair)
    {
        $cltpair = $this->unmask_pass($celpair, 2022, 'E');
        return $cltpair;
    }

    public function unmask_globals($type, $_key)
    {
        $translated = "";
        switch ($type) {
            case "COOKIE":
                if (isset($_COOKIE[$_key])) {
                    $translated = $this->unmask_pass($_COOKIE[$_key], 2022, 'D');
                }
                break;
        }

        return $translated;
    }
}
