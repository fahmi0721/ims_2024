<?php
session_start();
include_once '../../config/config.php';
include_once 'fungsi.php';

$UserUpdate = $_SESSION['Id'];
$date = date("Y-m-d H:i:s");
$proses = isset($_REQUEST['proses']) ? $_REQUEST['proses'] : "";
switch ($proses) {
	case 'DetailData':
		try {
			$data['a.NoKtp'] = $_POST['NoKtp'];
			$data['a.TanggalMulai'] = $_POST['TanggalMulai'];
			$result = DetailData($data);
		} catch (PDOException $e) {
			$result = $e->getMessage();
		}
		echo json_encode($result);
		break;
	case 'Crud':
		$aksi = $_POST['aksi'];
		
		switch ($aksi) {
			case 'insert':
				try {
					$data = array(
						"NoKtp" => $_POST['NoKtp'],
						"NoDokumen" => $_POST['NoDokumen'],
						"TanggalMulai" => $_POST['TanggalMulai'],
						"KodeBranch" => $_POST['KodeBranch'],
						"KodeCabang" => $_POST['KodeCabang'],
						"KodeDivisi" => $_POST['NamaKapal'],
						"KodeSubDivisi" => "001",
						"KodeSeksi" => $_POST['Jabatan'],
						"Keterangan" => $_POST['Keterangan'],
						"File" => $_FILES['File'],
						"Dir" => "../../File/SkPengangkatan/"
					);
					$pushdata = TambahData($data);
					echo json_encode($pushdata);
					
				} catch (PDOException $e) {
					$msg['status'] = 1;
					$msg['pesan'] = $e->getMessage();
					echo json_encode($msg);
				}
				break;
			case 'update':
				try {
					$data = array(
						"Id" => $_POST['Id'],
						"NoKtp" => $_POST['NoKtp'],
						"NoDokumen" => $_POST['NoDokumen'],
						"TanggalMulai" => $_POST['TanggalMulai'],
						"TanggalSelesai" => $_POST['TanggalSelesai'],
						"KodeBranch" => $_POST['KodeBranch'],
						"KodeCabang" => $_POST['KodeCabang'],
						"KodeDivisi" => $_POST['NamaKapal'],
						"KodeSubDivisi" => "001",
						"KodeSeksi" => $_POST['Jabatan'],
						"Keterangan" => $_POST['Keterangan'],
						"File" => $_FILES['File'],
						"Dir" => "../../File/SkPengangkatan/"
					);
					$pushdata = UbahData($data);
					echo json_encode($pushdata);
				} catch (PDOException $e) {
					$msg['status'] = 1;
					$msg['pesan'] = $e->getMessage();
					echo json_encode($msg);
				}
				
				
				break;
			case 'delete':
				try {
					$data = array(
						"Id" => $_POST['Id'],
						"Dir" => "../../File/SkPengangkatan/"
					);
					$pushdata = HapusData($data);
					echo json_encode($pushdata);
				} catch (PDOException $e) {
					$msg['status'] = 1;
					$msg['pesan'] = $e->getMessage();
					echo json_encode($msg);
				}
				break;
		}
		break;

	case 'ShowData':
		$Id = $_POST['Id'];
		$res = ShowData($Id);
		echo json_encode($res);
		break;
	case 'LoadData';
		$rule = $_POST['rule'];
		switch ($rule) {
			case 'TenagaKerja':
				$data = getTenagaKerja();
				echo json_encode($data);
				break;
			case 'Branch':
				$data = getBranch();
				echo json_encode($data);
				break;
			case 'UnitKerja':
				$data = getUnitKerja();
				echo json_encode($data);
				break;
			case 'Divisi':
				$data = getDivisi();
				echo json_encode($data);
				break;
			case 'SubDivisi':
				$data = getSubDivisi();
				echo json_encode($data);
				break;
			case 'Seksi':
				$data = getSeksi();
				echo json_encode($data);
				break;
		}

		break;
	
	
}

?>