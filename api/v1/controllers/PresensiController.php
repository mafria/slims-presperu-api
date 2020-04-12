<?php

require_once 'Controller.php';

class PresensiController extends Controller
{
    protected $sysconf;
    protected $db;

    function __construct($sysconf, $obj_db)
    {
        $this->sysconf = $sysconf;
        $this->db = $obj_db;
    }

    public function set($type, $token)
    {
        // $_POST = json_decode(file_get_contents('php://input'), true);
        // set key
        define('INDEX_AUTH_API', '1');
        // check auth
        $ca = $this->checkAuth($_POST['uniqueid']);

        if ($ca['status']) {
            require_once __DIR__ . '/../../../lib/presensi.inc.php';
            $presensi = new Presensi($this->db, $this->sysconf);
            $counter  = $presensi->setCounter($ca['data'][0]);
            parent::withJson(['status' => $counter['status'], 'msg' => $presensi->msg, 'data' => $counter['data']]);
            exit();
        }

        // else
        parent::withJson(['status' => 'fail', 'msg' => 'Anda tidak memiliki otorisasi. Pastikan kode unik sudah benar pada konfigurasi anda!']);
    }

    private function checkAuth($_str_token)
    {
        // query
        $_q = 'SELECT room_id FROM mst_room WHERE room_unique_id = "'.preg_replace('/[^0-9A-Za-z]/i', '', $_str_token).'"';
        // run query
        $_rq = $this->db->query($_q);

        if ($_rq->num_rows == 1) {
            $_d = $_rq->fetch_row();
            return ['status' => true, 'data' => $_d];
        }
        return ['status' => false];
    }
}