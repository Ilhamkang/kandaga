<?php
require_once __DIR__ . '/request.php';
require_once __DIR__ . '/upload.php';
function kandagaSaveLocation($editing) {
    global $conn; kandagaPost(true); $new=null;$directory=ROOT_PATH.'/assets/images';
    try {
        $id=(int)($_POST['id_lokasi']??0);$name=trim($_POST['nama_tempat']??'');$country=trim($_POST['negara']??'');$address=$_POST['alamat']??'';$description=$_POST['deskripsi']??'';
        if(($editing&&$id<1)||$name===''||$country===''||mb_strlen($name)>255)throw new InvalidArgumentException('Nama tempat dan negara wajib diisi; nama maksimal 255 karakter.');
        $old=null;
        if($editing){$stmt=$conn->prepare('SELECT foto_lokasi FROM tb_lokasi WHERE id_lokasi=?');$stmt->bind_param('i',$id);$stmt->execute();$old=$stmt->get_result()->fetch_assoc();if(!$old)throw new InvalidArgumentException('Lokasi tidak ditemukan.');}
        $new=kandagaUpload('gambar',$directory,'lokasi');$photo=$new??($old['foto_lokasi']??'');
        if($editing){$stmt=$conn->prepare('UPDATE tb_lokasi SET nama_tempat=?,negara=?,alamat=?,deskripsi=?,foto_lokasi=? WHERE id_lokasi=?');$stmt->bind_param('sssssi',$name,$country,$address,$description,$photo,$id);}
        else{$stmt=$conn->prepare('INSERT INTO tb_lokasi (nama_tempat,negara,alamat,deskripsi,foto_lokasi,tanggal_input) VALUES (?,?,?,?,?,NOW())');$stmt->bind_param('sssss',$name,$country,$address,$description,$photo);}
        $stmt->execute();echo 'success';
    }catch(Throwable $error){if($new)kandagaRemoveUpload($directory,$new);kandagaFailure($error);}
}
