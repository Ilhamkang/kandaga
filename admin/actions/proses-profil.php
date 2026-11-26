<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../includes/upload.php';
kandagaPost();$new=null;$directory=ROOT_PATH.'/assets/images/user';
try{
    $id=(int)$_SESSION['admin_id'];$nama=trim($_POST['nama']??'');$afiliasi=trim($_POST['afiliasi']??'');$deskripsi=$_POST['deskripsi']??'';$password=$_POST['password_baru']??'';
    if($nama===''||mb_strlen($nama)>100||mb_strlen($afiliasi)>255)throw new InvalidArgumentException('Nama wajib diisi (maksimal 100 karakter), afiliasi maksimal 255 karakter.');
    $new=kandagaUpload('foto_user',$directory,'user');
    $columns=['nama=?','afiliasi=?','deskripsi=?'];$values=[$nama,$afiliasi,$deskripsi];
    if($new){$columns[]='foto=?';$values[]=$new;}
    if($password!==''){$columns[]='password=?';$values[]=password_hash($password,PASSWORD_DEFAULT);}
    $types=str_repeat('s',count($values)).'i';$values[]=$id;
    $stmt=$conn->prepare('UPDATE users SET '.implode(',',$columns).' WHERE id=?');$stmt->bind_param($types,...$values);$stmt->execute();
    $_SESSION['nama']=$nama;kandagaJson(true,'Profil berhasil diperbarui.');
}catch(Throwable $error){if($new)kandagaRemoveUpload($directory,$new);kandagaFailure($error);}
