<?php
require_once __DIR__.'/../includes/version.php';
require_once __DIR__.'/includes/update-access.php';
$job=is_string($_GET['job']??null)?$_GET['job']:'';
if(!kandagaUpdateRecoveryAccess($job))kandagaAuthorize(true);
if(!preg_match('/^[a-f0-9]{32}$/D',$job)){http_response_code(404);exit('Pekerjaan tidak ditemukan.');}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pemulihan Kandaga</title><link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"><?php include __DIR__.'/../includes/module-head.php'; ?></head><body class="kandaga-module bg-light"><main class="container py-5" style="max-width:800px"><div class="card p-4"><div class="admin-page-header mb-4"><div><h1><i class="fa-solid fa-screwdriver-wrench me-2" aria-hidden="true"></i>Pemulihan Kandaga</h1><p>Halaman ini tetap dapat melanjutkan pemulihan ketika tabel akun sedang dipulihkan. Simpan sesi browser ini sampai proses selesai.</p></div></div><div id="recoveryState" role="status"></div><pre id="recoveryLog" style="white-space:pre-wrap;max-height:400px;overflow:auto"></pre><button id="resumeRecovery" class="btn btn-primary">Lanjutkan pemulihan</button><a class="btn btn-light border mt-3" href="<?= BASE_URL ?>/admin/index.php?page=update">Kembali ke Pusat Pembaruan</a></div></main>
<script>
const endpoint=<?= json_encode(BASE_URL.'/admin/actions/update-center.php',JSON_HEX_TAG) ?>,job=<?= json_encode($job) ?>,csrf=<?= json_encode($_SESSION['csrf_token']) ?>;
let running=false;
async function call(action){const r=await fetch(endpoint,{method:'POST',headers:{'X-CSRF-Token':csrf,'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action,job,csrf_token:csrf})});const j=await r.json();if(!j.success)throw Error(j.message);return j.data;}
function show(j){document.getElementById('recoveryState').textContent=j.state;document.getElementById('recoveryLog').textContent=j.log.map(x=>x.at+' — '+x.message).join('\n');}
async function run(){if(running)return;running=true;document.getElementById('resumeRecovery').disabled=true;try{let j=await call('job');if(j.state==='needs_recovery')j=await call('retry');show(j);while(['rollback_code','restore_db'].includes(j.state)){j=await call('tick');show(j);}if(j.state==='needs_recovery')throw Error(j.error||'Pemulihan terhenti.');}catch(e){document.getElementById('recoveryState').textContent=e.message;}finally{running=false;document.getElementById('resumeRecovery').disabled=false;}}
document.getElementById('resumeRecovery').onclick=run;run();
</script></body></html>
