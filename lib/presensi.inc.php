<?php

// be sure that this file not accessed directly
if (!defined('INDEX_AUTH_API')) {
    die("can not access this file directly");
} elseif (INDEX_AUTH_API != 1) {
    die("can not access this file directly");
}

define('INSTITUTION_EMPTY', 11);
define('ALREADY_CHECKIN', 12);

class Presensi
{
    private $dbs;
    private $sysconf;
    public $msg;

    public function __construct($dbs, $sysconf)
    {
        // load settings from database
        utility::loadSettings($dbs);
        // set db object
        $this->dbs = $dbs;
        // system configuration
        $this->sysconf = $sysconf;
    }

    public function checkVisit($str_member_ID, $ismember = true)
    {
        $limit_time_visit = $this->sysconf['time_visitor_limitation'];
        if ($ismember) {
        $criteria = 'member_id';
        } else {
        $criteria = 'member_name';
        }

        $date = date('Y-m-d');

        $_q = $this->dbs->query('SELECT checkin_date FROM visitor_count WHERE '.$criteria.'=\''.$str_member_ID.'\' ORDER BY checkin_date DESC LIMIT 1');
        if ($_q->num_rows > 0) {
        $_d = $_q->fetch_row();
        $time = new DateTime($_d[0]);
        $time->add(new DateInterval('PT'.$limit_time_visit.'M'));
        $timelimit = $time->format('Y-m-d H:i:s');
        $now = date('Y-m-d H:i:s');
        if ($now < $timelimit) {
            return true;
        }
        }

    return false;
  }

    public function setCounter($_int_room_id)
    {
        // set global
        // Took from lib/contents/visitor.inc.php
        // global
        /* object db */
        $dbs = $this->dbs;
        /* sysconf */
        $sysconf = $this->sysconf;
        /* custom msg */
        $_omsg = '';
        /* Post data */
        $member_name  = $dbs->escape_string(trim(strip_tags($_POST['memberID'])));
        $_institution = $dbs->escape_string(trim(strip_tags($_POST['institution'])));
        $_int_room_id = (int)$_int_room_id;
        // check if ID exists
        $str_member_ID = $dbs->escape_string($member_name);
        $_q = $dbs->query("SELECT member_id,member_name,member_image,inst_name, IF(TO_DAYS('".date('Y-m-d')."')>TO_DAYS(expire_date), 1, 0) AS is_expire FROM member WHERE member_id='$str_member_ID'");
        // if member is already registered
        if ($_q->num_rows > 0) {
            $_d = $_q->fetch_assoc();
            if ($_d['is_expire'] == 1) {
                // $expire = 1;
                $_omsg = '. Masa belarku keanggotaan anda sudah telah berakhir. Segera perbaharui kembali.';
            }
            $member_id      = $_d['member_id'];
            $member_name    = $_d['member_name'];
            $member_name    = preg_replace("/'/", "\'", $member_name);
            $photo          = trim($_d['member_image'])?trim($_d['member_image']):'person.png';
            $_institution   = $dbs->escape_string(trim($_d['inst_name']))?$dbs->escape_string(trim($_d['inst_name'])):null;
            
            $_checkin_date  = date('Y-m-d H:i:s');

            $_checkin_sql   = "INSERT INTO visitor_count (member_id, member_name, institution, checkin_date, room_id) VALUES ('$member_id', '$member_name', '$_institution', '$_checkin_date', '$_int_room_id')";
            
            // limitation
            if ($sysconf['enable_visitor_limitation']) {
                $already_checkin = $this->checkVisit($member_id, true);
                if ($already_checkin) {
                    $this->msg = 'Hi '.$member_name.', Selamat datang kembali '.$_omsg;
                    return ['status' => 'ok', 'data' => ['img' => $photo]];
                } else {
                    $_i = $dbs->query($_checkin_sql);
                    $this->msg = 'Hi '.$member_name.', Selamat datang perpustakaan kami '.$_omsg;
                    return ['status' => 'ok', 'data' => ['img' => $photo]];
                }
            } else {
                $_i = $dbs->query($_checkin_sql);
                $this->msg = 'Hi '.$member_name.', Selamat datang perpustakaan kami '.$_omsg;
                return ['status' => 'ok', 'data' => ['img' => $photo]];
            }
        } else {
        // non member
            $_d = $_q->fetch_assoc();
            $photo = 'non_member.png';
            $_checkin_date = date('Y-m-d H:i:s');
            if (!$_institution) {
                $this->msg = 'Anda belum terdaftar sebagai anggota, harap isi nama dan institusi anda.';
                return ['status' => 'fail', 'data' => ['img' => $photo]];
            } else {
            $_checkin_sql = "INSERT INTO visitor_count (member_name, institution, checkin_date, room_id) VALUES ('$member_name', '$_institution', '$_checkin_date', '$_int_room_id')";
            // limitation
            if ($sysconf['enable_visitor_limitation']) {
                $already_checkin = $this->checkVisit($member_name, false);
                if ($already_checkin) {
                    $this->msg = 'Hi '.$member_name.', Selamat datang kembali';
                    return ['status' => 'ok', 'data' => ['img' => $photo]];
                } else {
                    $_i = $dbs->query($_checkin_sql);
                    $this->msg = 'Hi '.$member_name.', Selamat datang perpustakaan kami.';
                    return ['status' => 'ok', 'data' => ['img' => $photo]];
                }
            } else {
                $_i = $dbs->query($_checkin_sql);
                $this->msg = 'Hi '.$member_name.', Selamat datang perpustakaan kami.';
                return ['status' => 'ok', 'data' => ['img' => $photo]];
            }
            }
        }
        return true;
    }
}
