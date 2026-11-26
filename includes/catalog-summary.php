<?php
function kandagaCatalogSummary($conn) {
    static $summary;
    if ($summary!==null)return $summary;
    $summary=[];
    foreach (['naskah'=>'SELECT COUNT(*) AS total FROM katalog','lembaga'=>'SELECT COUNT(*) AS total FROM tb_lokasi','negara'=>"SELECT COUNT(DISTINCT negara) AS total FROM tb_lokasi WHERE negara IS NOT NULL AND negara<>''",'bahasa'=>'SELECT COUNT(DISTINCT j.id_bahasa) AS total FROM naskah_bahasa j JOIN katalog k ON k.id=j.id_naskah JOIN tb_bahasa m ON m.id_bahasa=j.id_bahasa','aksara'=>'SELECT COUNT(DISTINCT j.id_aksara) AS total FROM naskah_aksara j JOIN katalog k ON k.id=j.id_naskah JOIN tb_aksara m ON m.id_aksara=j.id_aksara','alas_tulis'=>'SELECT COUNT(DISTINCT j.id_alas_tulis) AS total FROM naskah_alas_tulis j JOIN katalog k ON k.id=j.id_naskah JOIN tb_alas_tulis m ON m.id_alas_tulis=j.id_alas_tulis'] as $key=>$sql)$summary[$key]=(int)$conn->query($sql)->fetch_assoc()['total'];
    return $summary;
}
function kandagaCatalogBadges($conn) {
    $summary=kandagaCatalogSummary($conn);$html='<div class="catalog-type-badges d-flex flex-wrap mb-3" aria-label="Jumlah jenis metadata katalog">';
    foreach (['bahasa'=>['Bahasa','fa-language'],'aksara'=>['Aksara','fa-pen-fancy'],'alas_tulis'=>['Alas tulis','fa-scroll']] as $key=>[$label,$icon])$html.='<span class="badge bg-light text-dark border px-3 py-2 me-2 mb-2"><i class="fa-solid '.$icon.' me-1" aria-hidden="true"></i>'.$label.' <strong class="ms-1">'.number_format($summary[$key]).'</strong></span>';
    return $html.'</div>';
}
