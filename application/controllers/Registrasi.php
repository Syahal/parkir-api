<?php
defined('BASEPATH') or exit('No direct script access allowed');
date_default_timezone_set('Asia/Jakarta');

class Registrasi extends CI_Controller
{
    private $platNomor;
    private $warna;
    private $tipe;
    private $kapasitas;

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
                    case 'warna':
                        $this->warna = $val;
                        break;
                    case 'tipe':
                        $this->tipe = $val;
                        break;
                }
            }
        }

        // Only SUV and MPV that accepted in park
        if (!($this->tipe === 'MPV' or $this->tipe === 'SUV')) {
            $this->output->set_status_header(500)->set_content_type('application/json');
            echo json_encode(array('code' => 500, 'msg' => "Tipe Mobil Harus SUV atau MPV"));
            die();
        }

        //Set Kapasitas
        $this->kapasitas = 100;
    }

    public function index()
    {
        header('Content-Type: application/json');
        echo json_encode(array('code' => 404, 'msg' => "Not Found"));
        die();
    }

    public function add()
    {

        $jamMasuk = date('Y-m-d H:i:s');
        $succesWriteFile = false;
        $jumlahBayar = $this->tipe === 'SUV' ? 25000 : 35000;

        $lotArray = array();
        $selectedLot = null;

        // Create facility if not exist
        if (!file_exists('./data/lot.json')) {

            for ($i = 1; $i <= $this->kapasitas; $i++) {
                $dataLot = array(
                    'lot' => 'A' . $i,
                    'status' => $i !== 1 ? 'Available' : 'Not Available',
                );

                array_push($lotArray, $dataLot);
            }

            $selectedLot = array(
                'lot' => 'A1',
                'status' => 'Not Available',
            );
        } else {
            $dataString = file_get_contents('./data/lot.json');
            $lotArray = json_decode($dataString);
            $noEmptyLot = true;
            $selectedLotIndex = 0;

            foreach ($lotArray as $key => $val) {
                if ($val->status === 'Available') {
                    $selectedLot = $val;
                    $noEmptyLot = false;
                    break;
                }

            }

            // Search Key Lot
            for ($i = 0; $i < count($lotArray); $i++) {
                if ($lotArray[$i] === $selectedLot) {
                    $lotArray[$i] = array(
                        'lot' => $selectedLot->lot,
                        'status' => 'Not Available',
                    );
                }
            }

            // Lot full
            if ($noEmptyLot) {
                $this->output->set_status_header(500)->set_content_type('application/json');
                echo json_encode(array('code' => 500, 'msg' => "Mohon hubungi admin untuk menambahkan lot baru"));
                die();
            }
        }

        // Create data for success return
        $dataArray = array(
            'plat_nomor' => $this->platNomor,
            'parking_lot' => is_object($selectedLot) ? $selectedLot->lot : $selectedLot['lot'],
            'tanggal_masuk' => $jamMasuk,
            'tanggal_keluar' => '',
            'warna' => $this->warna,
            'tipe' => $this->tipe,
            'jumlah_bayar' => $jumlahBayar,
        );

        //Create a new list data parkir
        $listData = array();

        // Check if data(s) has been exist
        if (file_exists('./data/registrasi.json')) {
            // Read from existing data
            $dataString = file_get_contents('./data/registrasi.json');

            // Turn to list object through json decode
            $listData = json_decode($dataString);

            // Check if car with same number has parked and never leave
            foreach ($listData as $key => $val) {
                if ($val->plat_nomor === $this->platNomor && $val->tanggal_keluar === '') {
                    $this->output->set_status_header(500)->set_content_type('application/json');
                    echo json_encode(array('code' => 500, 'msg' => "Mobil sudah registrasi dan belum keluar"));
                    die();
                }
            }

        }

        // Push new data
        array_push($listData, $dataArray);

        // Replace file
        $succesWriteFile1 = write_file('./data/registrasi.json', json_encode($listData));
        $succesWriteFile2 = write_file('./data/lot.json', json_encode($lotArray));

        // Return http response
        header('Content-Type: application/json');
        if ($succesWriteFile1 && $succesWriteFile2) {
            unset($dataArray['warna']);
            unset($dataArray['jumlah_bayar']);
            unset($dataArray['tipe']);
            unset($dataArray['tanggal_keluar']);
            echo json_encode($dataArray);
        } else {
            $this->output->set_status_header(500)->set_content_type('application/json');
            echo json_encode(array('code' => 500, 'msg' => "Internal server error"));
        }

    }

}
