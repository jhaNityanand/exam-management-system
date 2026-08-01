<?php

/**
 * Place a single Examtube.in wordmark in the bottom-right of curated SEO PNGs.
 * Soft-fades the corner first so any AI-rendered brand text is replaced cleanly.
 *
 * Run: php scripts/brand-seo-images.php
 */

$outDir = dirname(__DIR__).'/public/frontend/images/seo';
$font = is_file('C:/Windows/Fonts/seguisb.ttf')
    ? 'C:/Windows/Fonts/seguisb.ttf'
    : 'C:/Windows/Fonts/segoeuib.ttf';

if (! is_file($font)) {
    fwrite(STDERR, "Segoe UI font not found.\n");
    exit(1);
}

$brand = 'Examtube.in';
$size = 18;

foreach (glob($outDir.'/*.png') as $path) {
    $img = imagecreatefrompng($path);
    if (! $img) {
        echo 'Skip '.basename($path)."\n";
        continue;
    }

    imagealphablending($img, true);
    imagesavealpha($img, true);

    $w = imagesx($img);
    $h = imagesy($img);

    // Soft left→right / top→bottom fade only in the brand corner (no hard box)
    $fadeW = 260;
    $fadeH = 64;
    $x0 = $w - $fadeW;
    $y0 = $h - $fadeH;
    for ($x = $x0; $x < $w; $x++) {
        for ($y = $y0; $y < $h; $y++) {
            $tx = ($x - $x0) / max(1, $fadeW - 1);
            $ty = ($y - $y0) / max(1, $fadeH - 1);
            $strength = $tx * $ty;
            if ($strength < 0.08) {
                continue;
            }
            // GD alpha: 0 opaque … 127 transparent
            $a = (int) round(127 - (70 * $strength));
            $col = imagecolorallocatealpha($img, 11, 18, 32, max(20, min(120, $a)));
            imagesetpixel($img, $x, $y, $col);
        }
    }

    $box = imagettfbbox($size, 0, $font, $brand);
    $brandW = abs($box[2] - $box[0]);
    $brandX = $w - 48 - $brandW;
    $brandY = $h - 34;

    $shadow = imagecolorallocatealpha($img, 0, 0, 0, 50);
    imagettftext($img, $size, 0, $brandX + 1, $brandY + 1, $shadow, $font, $brand);

    $text = imagecolorallocate($img, 226, 232, 240);
    imagettftext($img, $size, 0, $brandX, $brandY, $text, $font, $brand);

    $dot = imagecolorallocate($img, 45, 212, 191);
    imagefilledellipse($img, $brandX - 14, $brandY - 7, 7, 7, $dot);

    imagepng($img, $path, 8);
    imagedestroy($img);

    echo 'Branded '.basename($path).' ('.round(filesize($path) / 1024).' KB)'."\n";
}

echo "Done.\n";
