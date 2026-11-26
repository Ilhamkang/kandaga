<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/geo-analytics.php';
if(!kandagaAnalyticsSchemaReady($conn)){fwrite(STDERR,"Pasang migrations/004_analytics.sql terlebih dahulu.\n");exit(1);}
try {
    $days=kandagaAnalyticsSettings()['detail_retention_days'];
    $stmt=$conn->prepare('UPDATE website_visits SET ip_address=NULL,user_agent=NULL,search_term=NULL WHERE visited_at<DATE_SUB(NOW(),INTERVAL ? DAY) AND (ip_address IS NOT NULL OR user_agent IS NOT NULL OR search_term IS NOT NULL)');
    $stmt->bind_param('i',$days);$stmt->execute();$redacted=$stmt->affected_rows;$stmt->close();
    // Clear unused IP cache; website totals remain, location detail follows retention.
    $conn->query("DELETE g FROM website_geo_cache g LEFT JOIN website_visits v ON v.ip_address=g.ip_address WHERE v.id IS NULL");
    $geo=kandagaResolveGeoBatch($conn,10);
    echo json_encode(['redacted'=>$redacted]+$geo,JSON_UNESCAPED_UNICODE)."\n";
} catch(Throwable $error){fwrite(STDERR,"Pemeliharaan analitik belum berhasil; periksa database dan koneksi layanan lokasi.\n");exit(1);}
