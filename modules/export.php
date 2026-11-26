<?php
require_once __DIR__ . '/../config/bootstrap.php';
// Aktifkan error reporting untuk mempermudah debugging selama pengembangan
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Menggunakan path absolut mundur satu tingkat ke folder utama untuk database
require_once __DIR__ . '/../config/database.php';

// 1. Validasi Parameter Input
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['format'])) {
    http_response_code(400);
    die("Kelebihan/Kekurangan Parameter: ID dan Format wajib diisi.");
}

$id = intval($_GET['id']);
$format = strtolower(trim($_GET['format']));

// 2. Query Utama Ambil Data Manuskrip Lengkap
$query = "SELECT k.*, l.nama_tempat, l.negara, l.alamat
          FROM katalog k 
          LEFT JOIN tb_lokasi l ON k.id_lokasi = l.id_lokasi 
          WHERE k.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$naskah = $stmt->get_result()->fetch_assoc();

if (!$naskah) {
    http_response_code(404);
    die("Data manuskrip tidak ditemukan.");
}

// 3. Helper Fungsi Relasi Many-to-Many (Aksara, Bahasa, Alas Tulis)
function getRelasiArray($conn, $tableMaster, $tableJunction, $colId, $colNama, $id_naskah) {
    $sql = "SELECT DISTINCT m.{$colNama} FROM {$tableMaster} m
            JOIN {$tableJunction} j ON m.{$colId} = j.{$colId}
            WHERE j.id_naskah = ? ORDER BY m.{$colNama}";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_naskah);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($r = $res->fetch_assoc()) { $items[] = $r[$colNama]; }
    return $items;
}

$aksara_arr = getRelasiArray($conn, 'tb_aksara', 'naskah_aksara', 'id_aksara', 'nama_aksara', $id);
$bahasa_arr = getRelasiArray($conn, 'tb_bahasa', 'naskah_bahasa', 'id_bahasa', 'nama_bahasa', $id);
$alas_arr   = getRelasiArray($conn, 'tb_alas_tulis', 'naskah_alas_tulis', 'id_alas_tulis', 'nama_alas_tulis', $id);

$string_aksara = kandagaFormatConjunction($aksara_arr);
$string_bahasa = kandagaFormatConjunction($bahasa_arr);
$string_alas   = kandagaFormatConjunction($alas_arr);

// Relasi master dapat memuat "&" atau "<"; siapkan versi aman untuk XML.
$xml_aksara = htmlspecialchars($string_aksara, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$xml_bahasa = htmlspecialchars($string_bahasa, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$xml_alas   = htmlspecialchars($string_alas, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Sanitisasi data teks dasar untuk XML/Output
$judul = htmlspecialchars($naskah['judul'] ?? 'Tanpa Judul');
$kode  = htmlspecialchars($naskah['kode_inventaris'] ?? '-');
$tempat= htmlspecialchars($naskah['nama_tempat'] ?? '-');
$riwayat = htmlspecialchars(strip_tags($naskah['riwayat_naskah'] ?? ''));
$fisik = htmlspecialchars(strip_tags($naskah['deskripsi_fisik'] ?? ''));
$isi   = htmlspecialchars(strip_tags($naskah['deskripsi_isi'] ?? ''));

// 4. Routing Proses Ekspor Berdasarkan Format
switch ($format) {

    // ==================== 1. FORMAT CSV ====================
    case 'csv':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="metadata_naskah_'.$id.'.csv"');
        
        $output = fopen('php://output', 'w');
        // Baris Header CSV
        fputcsv($output, ['ID', 'Judul', 'Kode Inventaris', 'Tempat Penyimpan', 'Aksara', 'Bahasa', 'Alas Tulis', 'Riwayat', 'Deskripsi Fisik', 'Deskripsi Isi']);
        // Baris Data
        fputcsv($output, [$id, $naskah['judul'], $naskah['kode_inventaris'], $naskah['nama_tempat'], $string_aksara, $string_bahasa, $string_alas, $riwayat, $fisik, $isi]);
        fclose($output);
        exit;

    // ==================== 2. FORMAT MODS (Library of Congress) ====================
    case 'mods':
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: inline; filename="mods_naskah_'.$id.'.xml"');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<mods xmlns="http://www.loc.gov/mods/v3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd">'."\n";
        echo "  <titleInfo>\n    <title>{$judul}</title>\n  </titleInfo>\n";
        echo "  <identifier type=\"local\">{$kode}</identifier>\n";
        echo "  <location>\n    <physicalLocation>{$tempat}</physicalLocation>\n  </location>\n";
        echo "  <physicalDescription>\n    <form authority=\"gmd\">manuscript</form>\n    <note type=\"physical\">{$fisik}</note>\n    <note type=\"material\">{$xml_alas}</note>\n  </physicalDescription>\n";
        echo "  <language>\n    <languageTerm type=\"text\">{$xml_bahasa}</languageTerm>\n  </language>\n";
        echo "  <note type=\"content\">{$isi}</note>\n";
        echo "  <note type=\"ownership\">{$riwayat}</note>\n";
        echo '</mods>';
        exit;

    // ==================== 3. FORMAT PICA+ (Sistem Katalog Sentral Jerman) ====================
    case 'pica':
    case 'pica+':
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: inline; filename="pica_naskah_'.$id.'.txt"');
        
        // Format representasi PICA+ tekstual berbasis tag lapangan dan subfield ($a, $b)
        echo "001A \$aKANDAGA\n";
        echo "003@ \$0{$id}\n";
        echo "021A \$a{$judul}\n";
        echo "032A \$a{$kode}\n";
        echo "037I \$aLanguage: {$string_bahasa} \$bScript: {$string_aksara}\n";
        echo "041A \$a{$tempat}\n";
        echo "044G \$aMaterial: {$string_alas} \$bPhysical description: {$fisik}\n";
        echo "045Q \$aSummary: {$isi}\n";
        exit;

    // ==================== 4. FORMAT MARC21 (XML) ====================
    case 'marc21':
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: inline; filename="marc21_naskah_'.$id.'.xml"');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<record xmlns="http://www.loc.gov/MARC21/slim" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.loc.gov/MARC21/slim http://www.loc.gov/standards/marcxml/Schema/MARC21slim.xsd">'."\n";
        echo "  <leader>00000nam a2200000   4500</leader>\n";
        echo "  <controlfield tag=\"001\">{$id}</controlfield>\n";
        // 041 - Language
        echo "  <datafield tag=\"041\" ind1=\" \" ind2=\" \">\n    <subfield code=\"a\">{$xml_bahasa}</subfield>\n  </datafield>\n";
        // 099 - Local Call Number (Kode Inventaris)
        echo "  <datafield tag=\"099\" ind1=\" \" ind2=\" \">\n    <subfield code=\"a\">{$kode}</subfield>\n  </datafield>\n";
        // 245 - Title Info
        echo "  <datafield tag=\"245\" ind1=\"1\" ind2=\"0\">\n    <subfield code=\"a\">{$judul}</subfield>\n  </datafield>\n";
        // 300 - Physical Description
        echo "  <datafield tag=\"300\" ind1=\" \" ind2=\" \">\n    <subfield code=\"a\">{$fisik}</subfield>\n    <subfield code=\"b\">{$xml_alas}</subfield>\n  </datafield>\n";
        // 500 - General Note (Aksara)
        echo "  <datafield tag=\"500\" ind1=\" \" ind2=\" \">\n    <subfield code=\"a\">Script: {$xml_aksara}</subfield>\n  </datafield>\n";
        // 520 - Summary Note (Isi)
        echo "  <datafield tag=\"520\" ind1=\" \" ind2=\" \">\n    <subfield code=\"a\">{$isi}</subfield>\n  </datafield>\n";
        // 852 - Location (Tempat Penyimpan)
        echo "  <datafield tag=\"852\" ind1=\" \" ind2=\" \">\n    <subfield code=\"a\">{$tempat}</subfield>\n  </datafield>\n";
        echo '</record>';
        exit;

    // ==================== 5. FORMAT TEI (Text Encoding Initiative XML) ====================
    case 'tei':
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: inline; filename="tei_naskah_'.$id.'.xml"');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<TEI xmlns="http://www.tei-c.org/ns/1.0">'."\n";
        echo "  <teiHeader>\n";
        echo "    <fileDesc>\n";
        echo "      <titleStmt>\n        <title type=\"main\">Digital Edition of Manuskrip: {$judul}</title>\n      </titleStmt>\n";
        echo "      <publicationStmt>\n        <publisher>KANDAGA Repository</publisher>\n      </publicationStmt>\n";
        echo "      <sourceDesc>\n";
        echo "        <msDesc>\n";
        echo "          <msIdentifier>\n";
        echo "            <repository>{$tempat}</repository>\n";
        echo "            <idno>{$kode}</idno>\n";
        echo "          </msIdentifier>\n";
        echo "          <msContents>\n";
        echo "            <summary>{$isi}</summary>\n";
        echo "            <textLang mainLang=\"...\">Languages: {$xml_bahasa} | Scripts: {$xml_aksara}</textLang>\n";
        echo "          </msContents>\n";
        echo "          <physDesc>\n";
        echo "            <objectDesc form=\"manuscript\">\n";
        echo "              <supportDesc material=\"...\">\n                <support><p>{$xml_alas}. {$fisik}</p></support>\n              </supportDesc>\n";
        echo "            </objectDesc>\n";
        echo "          </physDesc>\n";
        echo "          <history>\n            <provenance><p>{$riwayat}</p></provenance>\n          </history>\n";
        echo "        </msDesc>\n";
        echo "      </sourceDesc>\n";
        echo "    </fileDesc>\n";
        echo "  </teiHeader>\n";
        echo "  <text>\n    <body>\n      <p>Transkripsi/Teks naskah digital belum disematkan.</p>\n    </body>\n  </text>\n";
        echo '</TEI>';
        exit;

    // ==================== 6. FORMAT HSP-KOD (Handschriftenportal - Kodikologi XML) ====================
    case 'hsp-kod':
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: inline; filename="hsp_kodikologi_'.$id.'.xml"');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<hsp xmlns="http://handschriftenportal.de/schema/hsp/v1" type="kodicology" id="' . $id . '">'."\n";
        echo "  <metadata>\n";
        echo "    <settlement>{$tempat}</settlement>\n";
        echo "    <idno>{$kode}</idno>\n";
        echo "    <title>{$judul}</title>\n";
        echo "    <objectDesc>\n";
        echo "      <supportDesc>\n";
        echo "        <material>{$xml_alas}</material>\n";
        echo "        <dimensionsDesc>{$fisik}</dimensionsDesc>\n";
        echo "      </supportDesc>\n";
        echo "    </objectDesc>\n";
        echo "    <historyDesc>\n";
        echo "      <provenance>{$riwayat}</provenance>\n";
        echo "    </historyDesc>\n";
        echo "  </metadata>\n";
        echo '</hsp>';
        exit;

    // ==================== 7. FORMAT HSP-DESC (Handschriftenportal - Deskripsi Teks XML) ====================
    case 'hsp-desc':
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: inline; filename="hsp_description_'.$id.'.xml"');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<hsp xmlns="http://handschriftenportal.de/schema/hsp/v1" type="description" id="' . $id . '">'."\n";
        echo "  <metadata>\n";
        echo "    <idno>{$kode}</idno>\n";
        echo "    <msContents>\n";
        echo "      <textLang>\n";
        echo "        <lang>{$xml_bahasa}</lang>\n";
        echo "        <script>{$xml_aksara}</script>\n";
        echo "      </textLang>\n";
        echo "      <contentDescription>\n";
        echo "        <summary>{$isi}</summary>\n";
        echo "      </contentDescription>\n";
        echo "    </msContents>\n";
        echo "  </metadata>\n";
        echo '</hsp>';
        exit;

    // Jika format tidak dikenali
    default:
        http_response_code(415);
        die("Format ekspor metadata '" . htmlspecialchars($format) . "' belum didukung.");
}
