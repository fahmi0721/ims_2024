<?php

    function Filter($str){
        if(empty($str)){
            return "";
        }else{
            return "WHERE (a.NoKtp LIKE '%".$str."%' OR c.Nama LIKE '%".$str."%')";
        }
    }
    function DetailData($data){
        $db = $GLOBALS['db'];
        $result = array();
        $row = array(); 
        if(is_array($data)){
            $Search = $data['Search'];
            $Page = $data['Page'];
            $RowPage = $data['RowPage'];
            $offset=($Page - 1) * $RowPage;
            $no=$offset+1;
            $FilterSearch = Filter($Search);
            $sql = "SELECT a.Keterangan, a.NoSertifikat, a.NoKtp, a.Flag, a.Id, a.Dari, a.Sampai, b.Nama as NamaMaster, a.File, c.Nama as TK FROM ims_pendidikan_nonformal a INNER JOIN ims_master_pendidikan_nonformal b ON a.KodeMaster = b.Kode  INNER JOIN ims_master_tenaga_kerja c ON a.NoKtp = c.NoKtp $FilterSearch";
            $query = $db->query($sql);
            $JumRow = $query->rowCount();
            $total_page = ceil($JumRow / $RowPage);
            $result['total_page'] = $total_page;
            $result['total_data'] = $JumRow;
            $result['data_new'] = $no;
            $sql = $sql." ORDER BY Id DESC LIMIT ".$offset.",".$RowPage;
            $query = $db->query($sql);
            $Flag = array("0"=>"<center><label class='label label-danger'>Tidak Aktif<label></center>","1"=>"<center><label class='label label-success'>Aktif<label></center>");
            if($JumRow > 0){
                while ($res = $query->fetch(PDO::FETCH_ASSOC)) { 
                    if(!empty($res['File'])){
                        $extensi = getExtensi($res['File']);
                        if($extensi == "pdf"){
                            $aksi = "<a target='_blank' href='File/PendidikanNonFormal/".$res['File']."' class='btn btn-xs btn-success' data-toggle='tooltip' title='Lihat Dokumen' ><i class='fa fa-file-pdf-o'></i></a>";
                        }else{
                            $aksi = "<a class='btn btn-xs btn-success' data-toggle='tooltip' title='Lihat Dokumen' onclick=\"Crud('".$res['File']."', 'file')\"><i class='fa fa-picture-o'></i></a>";
                        }
                    }else{
                        $aksi = "";
                    }
                    $aksi .= "<a class='btn btn-xs btn-primary' data-toggle='tooltip' title='Ubah Data' onclick=\"Crud('".$res['Id']."', 'ubah')\"><i class='fa fa-edit'></i></a> <a class='btn btn-xs btn-danger' data-toggle='tooltip' title='Hapus Data' onclick=\"Crud('".$res['Id']."', 'hapus')\"><i class='fa fa-trash-o'></i></a>";
                    $row['No'] = $no;
                    $row['TK'] = $res['TK']."<br><small><b>No KTP : ".$res['NoKtp']."</b></small>";
                    $row['Pendidikan'] = $res['NamaMaster']."<br><small>No Sertifikat : ".$res['NoSertifikat']."</small>";
                    $row['Berlaku'] = $res['Sampai'] == "0000-00-00" ? "Tanggal Mulai : ".tgl_indo($res['Dari']) : "Tanggal Mulai : ".tgl_indo($res['Dari'])."<br>Berakhir : ".tgl_indo($res['Sampai']);
                    $row['Flag'] = $Flag[$res['Flag']];
                    $row['Keterangan'] = $res['Keterangan'];
                    $row['Aksi'] = "<div class='btn-group'>".$aksi."</div>";
                    $result['data'][] = $row;
                    $no++;
                }
                $result['data_last'] = $no -1;
                return $result; 
            }else{
                $result['data']="";
                return $result; 
            }
        }
    }

    function InsertLogs($msg){
        $Logs['UserId'] = $_SESSION['Id'];
        $Logs['Logs'] = $msg;
        $Logs['Modul'] = $_SESSION['page'];
        Logs($Logs);
    }

    function getExtensi($NamaGambar){
        $lower = strtolower($NamaGambar);
        $explo = explode(".",$lower);
        $extensi  = end($explo);
        return $extensi;
    }

    function ValidasiFile($Foto,$Dir){
        $ExtensArr = array("jpg","jpeg","png","pdf");
        $NamaGambar = $Foto['name'];
        $TmpGambar = $Foto['tmp_name'];
        $SizeGambar = round($Foto['size']/1024,0);
        $Extensi = getExtensi($NamaGambar);
        
        
        if(!in_array($Extensi,$ExtensArr)){
            $pesan = "File yang dimsukkan bukan gambar.";
            InsertLogs($pesan);
            $r['status'] = "error";
            $r['NewName'] = "";
            return $r;
        }else{
            if($SizeGambar > 2048){
                $pesan = "File yang dimsukkan terlalu besar. Maksimal file adalah 2 mb";
                InsertLogs($pesan);
                $r['status'] = "error";
                $r['NewName'] = "";
                return $r;
                exit();
            }else{
                $NewName = rand(0,9999).time().".".$Extensi;
                if(!move_uploaded_file($TmpGambar,$Dir.$NewName)){
                    InsertLogs($pesan);
                    $r['status'] = "error";
                    $r['NewName'] = "";
                    return $r;
                    exit();
                }else{
                    $r['status'] = "sukses";
                    $r['NewName'] = $NewName;
                    return $r;
                }
            }
        }
    }

    function getNamaMaster($Kode){
        $sql = "SELECT Nama FROM ims_master_pendidikan_nonformal WHERE Kode = '$Kode'";
        $query = $GLOBALS['db']->query($sql);
        $r = $query->fetch(PDO::FETCH_ASSOC);
        return $r['Nama'];
    }

    function UpdateMasterBiodata($NoKtp){
        try {
            $qr = $GLOBALS['db']->query("SELECT * FROM ims_pendidikan_nonformal WHERE NoKtp = '$NoKtp' ORDER BY Flag DESC, 	Dari DESC, Id DESC LIMIT 1");
            $rt = $qr->fetch(PDO::FETCH_ASSOC);
            if(empty($rt)){
                $rs = null;
            }else{
                $rt['NamaPendidikan'] = getNamaMaster($rt['KodeMaster']);
                $rs = json_encode($rt);
                $rs = base64_encode($rs);
            }
            $sql = "UPDATE ims_master_biodata SET  PendidikanNonFormal = '$rs', KodePendidikanNonFormal = '$rt[KodeMaster]' WHERE NoKtp = '$NoKtp'";
            $GLOBALS['db']->query($sql); 
            return true;
        } catch (PDOException $e) {
            return $e->errorMessage();
        }
    }

    function TambahData($data){
        $koneksi = $GLOBALS['db'];
        if(is_array($data)){
            try {
                $data['TglCreate'] = date("Y-m-d H:i:s");
                $data['UserId'] = $_SESSION['Id'];
                if(isset($data['File']) && !empty($data['File']['name'])){
                    $Img = ValidasiFile($data['File'],$data['Dir']);
                    if($Img['status'] == "sukses"){
                        $sql = "INSERT INTO ims_pendidikan_nonformal SET  NoKtp = :NoKtp, KodeMaster = :KodeMaster,  Dari = :Dari, Sampai = :Sampai, `File` = :Files, Keterangan = :Keterangan, Flag = :Flag,  TglCreate = :TglCreate,  UserId = :UserId, NoSertifikat = :NoSertifikat"; 
                        $exc = $koneksi->prepare($sql);
                        $exc->bindParam('NoKtp', $data['NoKtp'], PDO::PARAM_STR);
                        $exc->bindParam('KodeMaster', $data['KodeMaster'], PDO::PARAM_STR);
                        $exc->bindParam('Dari', $data['Dari'], PDO::PARAM_STR);
                        $exc->bindParam('Sampai', $data['Sampai'], PDO::PARAM_STR);
                        $exc->bindParam('Files', $Img['NewName'], PDO::PARAM_STR);
                        $exc->bindParam('Keterangan', $data['Keterangan'], PDO::PARAM_STR);
                        $exc->bindParam('Flag', $data['Flag'], PDO::PARAM_STR);
                        $exc->bindParam('TglCreate', $data['TglCreate'], PDO::PARAM_STR);
                        $exc->bindParam('UserId', $data['UserId'], PDO::PARAM_STR);
                        $exc->bindParam('NoSertifikat', $data['NoSertifikat'], PDO::PARAM_STR);
                        $exc->execute();
                        $msg['pesan'] = "Berhasil menambah data pendidikan non formal";
                        $rMsg = "Berhasil menambah data pendidikan non formal dengan No KTP <b>".$data['NoKtp']."</b>";
                        $msg['status'] = "sukses";
                        InsertLogs($rMsg);
                        UpdateMasterBiodata($data['NoKtp']);
                        return $msg;
                    }
                }else{
                    $sql = "INSERT INTO ims_pendidikan_nonformal SET  NoKtp = :NoKtp, KodeMaster = :KodeMaster, Dari = :Dari, Sampai = :Sampai,  Flag = :Flag, Keterangan = :Keterangan,  TglCreate = :TglCreate,  UserId = :UserId, NoSertifikat = :NoSertifikat"; 
                    $exc = $koneksi->prepare($sql);
                    $exc->bindParam('NoKtp', $data['NoKtp'], PDO::PARAM_STR);
                    $exc->bindParam('KodeMaster', $data['KodeMaster'], PDO::PARAM_STR);
                    $exc->bindParam('Dari', $data['Dari'], PDO::PARAM_STR);
                    $exc->bindParam('Sampai', $data['Sampai'], PDO::PARAM_STR);
                    $exc->bindParam('Flag', $data['Flag'], PDO::PARAM_STR);
                    $exc->bindParam('Keterangan', $data['Keterangan'], PDO::PARAM_STR);
                    $exc->bindParam('TglCreate', $data['TglCreate'], PDO::PARAM_STR);
                    $exc->bindParam('UserId', $data['UserId'], PDO::PARAM_STR);
                    $exc->bindParam('NoSertifikat', $data['NoSertifikat'], PDO::PARAM_STR);
                    $exc->execute();
                    $msg['pesan'] = "Berhasil menambah data pendidikan non formal";
                    $rMsg = "Berhasil menambah data pendidikan non formal dengan No KTP <b>".$data['NoKtp']."</b>";
                    $msg['status'] = "sukses";
                    InsertLogs($rMsg);
                    UpdateMasterBiodata($data['NoKtp']);
                    return $msg;
                }
               
                
            } catch (PDOException $e) {
                $msg['pesan'] = $e->errorMessage();
                $msg['status'] = "error";
                InsertLogs($msg['pesan']);
                return $msg;
            }
            
        }else{
            $msg['pesan'] = "Pengiriman data ke server gagal";
            $msg['status'] = "gagal";
            InsertLogs($msg['pesan']);
            return $msg;
        }
    }
    
    function HapusFile($Id,$Dir){
        $Img = ShowData($Id);
        $Source = $Dir.$Img['File'];
        if(!empty($Img['File']) && file_exists($Source)){
            unlink($Source);
            return true;
        }else{
            return true;
        }
    }

    function UbahData($data){
        $koneksi = $GLOBALS['db'];
         if(is_array($data)){
            try {
                $data['TglUpdate'] = date("Y-m-d H:i:s");
                $data['UserId'] = $_SESSION['Id'];
                if(isset($data['File']) && !empty($data['File']['name'])){
                    $Img = ValidasiFile($data['File'],$data['Dir']);
                    if($Img['status'] == "sukses"){
                        HapusFile($data['Id'],$data['Dir']);
                        $sql = "UPDATE ims_pendidikan_nonformal SET NoKtp = :NoKtp, KodeMaster = :KodeMaster, Dari = :Dari, Sampai = :Sampai, `File` = :Files, Flag = :Flag, Keterangan = :Keterangan,  TglUpdate = :TglUpdate , NoSertifikat = :NoSertifikat WHERE  Id = :Id"; 
                        $exc = $koneksi->prepare($sql);
                        $exc->bindParam('NoKtp', $data['NoKtp'], PDO::PARAM_STR);
                        $exc->bindParam('KodeMaster', $data['KodeMaster'], PDO::PARAM_STR);
                        $exc->bindParam('Dari', $data['Dari'], PDO::PARAM_STR);
                        $exc->bindParam('Sampai', $data['Sampai'], PDO::PARAM_STR);
                        $exc->bindParam('Files', $Img['NewName'], PDO::PARAM_STR);
                        $exc->bindParam('Flag', $data['Flag'], PDO::PARAM_STR);
                        $exc->bindParam('Keterangan', $data['Keterangan'], PDO::PARAM_STR);
                        $exc->bindParam('TglUpdate', $data['TglUpdate'], PDO::PARAM_STR);
                        $exc->bindParam('NoSertifikat', $data['NoSertifikat'], PDO::PARAM_STR);
                        $exc->bindParam('Id', $data['Id'], PDO::PARAM_STR);
                        $exc->execute();
                        $msg['pesan'] = "Berhasil mengubah data pendidikan non formal";
                        $rMsg = "Berhasil mengubah data pendidikan non formal dengan No KTP <b>".$data['NoKtp']."</b>";
                        $msg['status'] = "sukses";
                        InsertLogs($rMsg);
                        UpdateMasterBiodata($data['NoKtp']);
                        return $msg;
                    }
                }else{
                    $sql = "UPDATE ims_pendidikan_nonformal SET  NoKtp = :NoKtp, KodeMaster = :KodeMaster, Dari = :Dari, Sampai = :Sampai,  Flag = :Flag, Keterangan = :Keterangan,  TglUpdate = :TglUpdate, NoSertifikat = :NoSertifikat WHERE Id = :Id"; 
                    $exc = $koneksi->prepare($sql);
                    $exc->bindParam('NoKtp', $data['NoKtp'], PDO::PARAM_STR);
                    $exc->bindParam('KodeMaster', $data['KodeMaster'], PDO::PARAM_STR);
                    $exc->bindParam('Dari', $data['Dari'], PDO::PARAM_STR);
                    $exc->bindParam('Sampai', $data['Sampai'], PDO::PARAM_STR);
                    $exc->bindParam('Flag', $data['Flag'], PDO::PARAM_STR);
                    $exc->bindParam('Keterangan', $data['Keterangan'], PDO::PARAM_STR);
                    $exc->bindParam('TglUpdate', $data['TglUpdate'], PDO::PARAM_STR);
                    $exc->bindParam('NoSertifikat', $data['NoSertifikat'], PDO::PARAM_STR);
                    $exc->bindParam('Id', $data['Id'], PDO::PARAM_STR);
                    $exc->execute();
                    $msg['pesan'] = "Berhasil mengubah data pendidikan non formal";
                    $rMsg = "Berhasil mengubah data pendidikan non formal dengan No KTP <b>".$data['NoKtp']."</b>";
                    $msg['status'] = "sukses";
                    InsertLogs($rMsg);
                    UpdateMasterBiodata($data['NoKtp']);
                    return $msg;
                }
               
                
            } catch (PDOException $e) {
                $msg['pesan'] = $e->errorMessage();
                $msg['status'] = "error";
                InsertLogs($msg['pesan']);
                return $msg;
            }
            
        }else{
            $msg['pesan'] = "Pengiriman data ke server gagal";
            $msg['status'] = "gagal";
            InsertLogs($msg['pesan']);
            return $msg;
        }
    }

    function HapusData($data){
        $koneksi = $GLOBALS['db'];
        if(is_array($data)){
            try {
                $rs = ShowData($data['Id']);
                HapusFile($data['Id'], $data['Dir']);
                $sql = "DELETE FROM ims_pendidikan_nonformal WHERE Id = :Id";
                $exc = $koneksi->prepare($sql);
                $exc->bindParam('Id', $data['Id'], PDO::PARAM_INT);
                $exc->execute();
                $msg['pesan'] = "Berhasil menghapus data pendidikan non formal </b>";
                $rMsg = "Berhasil menghapus data pendidikan non formal dengan No KTP <b>".$rs['NoKtp']."</b>";
                $msg['status'] = "sukses";
                InsertLogs($rMsg);
                UpdateMasterBiodata($rs['NoKtp']);
                return $msg;
             } catch (PDOException $e) {
                $msg['pesan'] = $e->errorMessage();
                $msg['status'] = "error";
                InsertLogs($msg['pesan']);
                return $msg;
            }
        }else{
            $msg['pesan'] = "Pengiriman data ke server gagal";
            $msg['status'] = "gagal";
            InsertLogs($msg['pesan']);
            return $msg;
        }
    }

    function ShowData($Id){
        $koneksi = $GLOBALS['db'];
        $sql = "SELECT * FROM ims_pendidikan_nonformal WHERE Id = :Id";
        $exc = $koneksi->prepare($sql);
        $exc->bindParam("Id", $Id, PDO::PARAM_INT);
        $exc->execute();
        $dt = $exc->fetch(PDO::FETCH_ASSOC);
        return $dt;
    }

    function getTeagaKerja(){
        $r = array();
        $sql = "SELECT NoKtp, Nama  FROM ims_master_tenaga_kerja WHERE Flag = '1'";
        $query = $GLOBALS['db']->query($sql);
        $row = $query->rowCount();
        if($row > 0){
            while($res = $query->fetch(PDO::FETCH_ASSOC)){
                $r['data'][] = $res;
            }
            return $r;
        }
    }

    function getMasterPendidikanNonFormal(){
        $r = array();
        $sql = "SELECT Kode, Nama  FROM ims_master_pendidikan_nonformal WHERE Flag = '1'";
        $query = $GLOBALS['db']->query($sql);
        $row = $query->rowCount();
        if($row > 0){
            while($res = $query->fetch(PDO::FETCH_ASSOC)){
                $r['data'][] = $res;
            }
            return $r;
        }
    }
    

?>