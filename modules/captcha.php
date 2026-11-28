<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Ambiguous glyphs (I, O, 0, 1) are omitted so a human can still read a
// deliberately distorted challenge.
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 6; $i++) {
    $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
}
$_SESSION['captcha_code'] = $code;
$_SESSION['captcha_generated_at'] = time();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$width = 190;
$height = 58;
$inks = [
    [15, 118, 110], [17, 94, 89], [30, 58, 95], [51, 65, 85], [71, 85, 105],
];

// SVG fallback: each glyph keeps its own angle, skew, size, and baseline so
// even hosts without GD do not render a flat, machine-friendly text strip.
if (!function_exists('imagecreatetruecolor')) {
    header('Content-Type: image/svg+xml; charset=utf-8');
    $glyphs = [];
    for ($i = 0; $i < strlen($code); $i++) {
        $x = 16 + $i * 29;
        $y = 38 + random_int(-4, 4);
        $angle = random_int(-18, 18);
        $skew = random_int(-8, 8);
        $size = random_int(20, 25);
        $ink = $inks[array_rand($inks)];
        $glyphs[] = '<text x="' . $x . '" y="' . $y . '" transform="rotate(' . $angle . ' ' . $x . ' ' . $y . ') skewX(' . $skew . ')"'
            . ' font-family="DejaVu Sans, Verdana, sans-serif" font-style="italic" font-weight="700" font-size="' . $size . '"'
            . ' fill="rgb(' . implode(',', $ink) . ')">' . htmlspecialchars($code[$i], ENT_XML1, 'UTF-8') . '</text>';
    }
    $wave = [];
    for ($px = 0; $px <= $width; $px += 6) {
        $wave[] = ($px === 0 ? 'M' : 'L') . $px . ' ' . (30 + round(sin($px / 19) * 7));
    }
    $noise = '';
    for ($i = 0; $i < 90; $i++) {
        $noise .= '<circle cx="' . random_int(0, $width) . '" cy="' . random_int(0, $height) . '" r="' . random_int(1, 2) . '" fill="#cbd5e1" opacity=".7"/>';
    }
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">'
        . '<rect width="' . $width . '" height="' . $height . '" fill="#f8fafc"/>'
        . '<path d="' . implode(' ', $wave) . '" fill="none" stroke="#99f6e4" stroke-width="2"/>'
        . '<path d="M0 12 L190 42 M0 48 L190 8" stroke="#e2e8f0" stroke-width="1"/>'
        . $noise . implode('', $glyphs) . '</svg>';
    exit;
}

$image = imagecreatetruecolor($width, $height);
$backgrounds = [[248, 250, 252], [241, 245, 249], [240, 253, 250]];
$bg = $backgrounds[array_rand($backgrounds)];
imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, $bg[0], $bg[1], $bg[2]));

$grid = imagecolorallocatealpha($image, 148, 163, 184, 95);
for ($x = 0; $x <= $width; $x += 16) imageline($image, $x, 0, $x, $height, $grid);
for ($y = 0; $y <= $height; $y += 16) imageline($image, 0, $y, $width, $y, $grid);

$speck = imagecolorallocatealpha($image, 100, 116, 139, 105);
for ($i = 0; $i < 130; $i++) {
    imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $speck);
}

// Prefer an italic/oblique TrueType face when the host provides one. A
// built-in-font fallback rotates each glyph individually below.
$font = '';
$fontCandidates = [
    ROOT_PATH . '/assets/fonts/FreeSansOblique.ttf',
    '/usr/share/fonts/truetype/freefont/FreeSansOblique.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Oblique.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Italic.ttf',
    '/usr/share/fonts/truetype/crosextra/Caladea-Italic.ttf',
];
foreach ($fontCandidates as $candidate) {
    if (is_readable($candidate)) { $font = $candidate; break; }
}

imagealphablending($image, true);
for ($i = 0; $i < strlen($code); $i++) {
    $char = $code[$i];
    $x = 16 + $i * 29;
    $y = 38 + random_int(-4, 4);
    $angle = random_int(-18, 18);
    $ink = $inks[array_rand($inks)];
    if ($font !== '') {
        $color = imagecolorallocate($image, $ink[0], $ink[1], $ink[2]);
        imagettftext($image, random_int(21, 25), $angle, $x, $y, $color, $font, $char);
        continue;
    }
    // Isolate the built-in glyph, rotate it, then composite at the jittered
    // baseline so every character has a different slant.
    $glyph = imagecreatetruecolor(28, 38);
    imagealphablending($glyph, false);
    imagesavealpha($glyph, true);
    $clear = imagecolorallocatealpha($glyph, 0, 0, 0, 127);
    imagefilledrectangle($glyph, 0, 0, 28, 38, $clear);
    imagestring($glyph, 5, 5, 10, $char, imagecolorallocate($glyph, $ink[0], $ink[1], $ink[2]));
    $rotated = imagerotate($glyph, $angle, $clear);
    imagecopy($image, $rotated, $x, $y - 30, 0, 0, 28, 38);
    imagedestroy($rotated);
    imagedestroy($glyph);
}

// Two thin sine strokes cross the letters and break up row-based OCR.
$waveColor = imagecolorallocatealpha($image, 15, 118, 110, 105);
for ($pass = 0; $pass < 2; $pass++) {
    $previous = null;
    for ($px = 0; $px <= $width; $px += 3) {
        $py = (int)round(($pass === 0 ? 14 : 45) + sin($px / 21 + $pass * 1.8) * 5);
        if ($previous !== null) imageline($image, $previous[0], $previous[1], $px, $py, $waveColor);
        $previous = [$px, $py];
    }
}

header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
