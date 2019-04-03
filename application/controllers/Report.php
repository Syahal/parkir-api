<?php
defined('BASEPATH') or exit('No direct script access allowed');
date_default_timezone_set('Asia/Jakarta');

class Report extends CI_Controller
{
    private $tipe = null;
    private $warna = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('file');

        $data = json_decode(file_get_contents('php://input'), true);
        if ($data) {
            foreach ($data as $key => $val) {
                switch ($key) {
                    case 'tipe':
                        $this->tipe = $val;
                        break;

                    case 'warna':
                        $this->warna = $val;
                        break;
                }
            }
        }

        if (!file_exists('./data/lot.json') && !file_exists('./data/registrasi.json')) {
            $this->output->set_status_header(500)->set_content_type('application/json');
            echo json_encode(array('code' => 500, 'msg' => "Data belum tersedia"));
            die();
        }

        $this->listLot = json_decode(file_get_contents('./data/lot.json'));
        $this->listRegistrasi = json_decode(file_get_contents('./data/registrasi.json'));

    }

    public function index()
    {
        header('Content-Type: application/json');
        echo json_encode(array('code' => 404, 'msg' => "Not Found"));
        die();
    }

    public function jumlahpertipe()
    {
        if ($this->tipe === null) {
            die();
        }

        $jumlah = 0;
        foreach ($this->listRegistrasi as $key => $val) {
            if ($val->tipe === $this->tipe) {
                $jumlah++;
            }
        }

        echo json_encode(array(
            'tipe' => $this->tipe,
            'jumlah' => $jumlah,
        ));
    }

    public function platnomorperwarna()
    {
        if ($this->warna === null) {
            die();
        }

        $listCarByColor = array();
        foreach ($this->listRegistrasi as $key => $val) {
            if (strtolower($val->warna) === strtolower($this->warna)) {
                array_push($listCarByColor, $val->plat_nomor);
            }
        }

        echo json_encode(
            array(
                'warna' => $this->warna,
                'plat_nomor' => array_unique($listCarByColor))
            );
    }

}
