<?php
defined('BASEPATH') or exit('No direct script access allowed');
date_default_timezone_set('Asia/Jakarta');

class Keluar extends CI_Controller
{
    private $platNomor;
    private $listLot;
    private $listRegistrasi;
    private $tanggalKeluar;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('file');

        $data = json_decode(file_get_contents('php://input'), true);
        if ($data) {
            foreach ($data as $key => $val) {
                switch ($key) {
                    case 'plat_nomor':
                        $this->platNomor = $val;
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
        $this->tanggalKeluar = date('Y-m-d H:i:s');

    }

    public function index()
    {
        header('Content-Type: application/json');
        echo json_encode(array('code' => 404, 'msg' => "Not Found"));
        die();
    }

    public function add()
    {
        // Search for car by plat nomor and jam keluar empty
        $selectedCar = null;
        $isCarFound = false;

        foreach ($this->listRegistrasi as $key => $val) {
            if ($val->plat_nomor === $this->platNomor && $val->tanggal_keluar === '') {
                $isCarFound = true;
                $selectedCar = $val;
                break;
            }
        }

        // Car not found
        if (!$isCarFound) {
            $this->output->set_status_header(500)->set_content_type('application/json');
            echo json_encode(array(
                'code' => 500,
                'msg' => "Tidak ada kendaraan terparkir dengan nomor ".$this->platNomor
            ));

            die();
        }

        $selectedCar->tanggal_keluar = $this->tanggalKeluar;
        $biayaAwal = $selectedCar->jumlah_bayar;

        // Count Jumlah Bayar
        $jamMasuk = new DateTime($selectedCar->tanggal_masuk);
        $jamKeluar = new DateTime($selectedCar->tanggal_keluar);
        $lamaTime = date_diff($jamMasuk, $jamKeluar);
        $lamaHari = $lamaTime->days;

        $lamaJam = $lamaTime->h + ($lamaTime->i > 0 ? 1 : 0) + ($lamaHari * 24);
        

        $selectedCar->total_jam = $lamaJam;

        if ($selectedCar->total_jam > 1) {
            $selectedCar->jumlah_bayar
            = $biayaAwal + (($biayaAwal * 20 / 100) * ($lamaJam - 1));
        }

        // Convert to readble parking duration
        $selectedCar->total_jam = $lamaTime->h.' Jam '.$lamaTime->i.' Menit '.$lamaTime->s.' Detik';

        // Edit Data Registrasi
        for ($i = 0; $i < count($this->listRegistrasi); $i++) {
            if ($this->listRegistrasi[$i]->plat_nomor === $this->platNomor && $this->listRegistrasi[$i]->tanggal_keluar === '') {
                $this->listRegistrasi[$i] = $selectedCar;
                break;
            }
        }

        // Edit Data Lot
        for ($i = 0; $i < count($this->listLot); $i++) {
            if ($selectedCar->parking_lot === $this->listLot[$i]->lot) {
                $selectedLot = $this->listLot[$i];
                $selectedLot->status = 'Available';
                $this->listLot[$i]  = $selectedLot;
                break;
            }
        }

        //Todo Update Data
        $isLotUpdated = write_file('./data/lot.json', json_encode($this->listLot));
        $isRegistrasiUpdated = write_file('./data/registrasi.json', json_encode($this->listRegistrasi));

        if ($isRegistrasiUpdated && $isLotUpdated) {
            // Remove unecessery property
            unset($selectedCar->parking_lot);
            unset($selectedCar->warna);
            unset($selectedCar->tipe);
            echo json_encode($selectedCar);
        }

    }

}
