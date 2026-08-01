<?php

/**
 * Procedural fallback OG images (1200×630) for Examtube.in.
 * Brand wordmark only in the bottom-right corner.
 *
 * Curated AI PNGs in public/frontend/images/seo/*.png are preferred.
 * This script only regenerates PNG/SVG when --force is passed, or when a
 * PNG is missing. Prefer: php scripts/brand-seo-images.php on curated assets.
 *
 * Run: php scripts/generate-seo-images.php
 * Force: php scripts/generate-seo-images.php --force
 */

$force = in_array('--force', $argv ?? [], true);

$outDir = dirname(__DIR__).'/public/frontend/images/seo';
if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$fontRegular = 'C:/Windows/Fonts/segoeui.ttf';
$fontBold = 'C:/Windows/Fonts/segoeuib.ttf';
$fontSemi = is_file('C:/Windows/Fonts/seguisb.ttf')
    ? 'C:/Windows/Fonts/seguisb.ttf'
    : $fontBold;

if (! is_file($fontBold) || ! is_file($fontRegular)) {
    fwrite(STDERR, "Segoe UI fonts not found.\n");
    exit(1);
}

$cards = [
    'home' => [
        'eyebrow' => 'Learning platform',
        'title' => 'Practice smarter.',
        'subtitle' => "Timed mocks, questions, blogs & news\nin one clean place.",
        'colors' => [[15, 118, 110], [13, 148, 136], [45, 212, 191]],
        'icon' => 'home',
    ],
    'category' => [
        'eyebrow' => 'Explore topics',
        'title' => 'Find your lane.',
        'subtitle' => "Browse streams and subjects\nbuilt for focused prep.",
        'colors' => [[8, 145, 178], [14, 165, 233], [56, 189, 248]],
        'icon' => 'category',
    ],
    'exam' => [
        'eyebrow' => 'Assessments',
        'title' => 'Timed mock exams.',
        'subtitle' => "Real scoring rules, attempt tracking,\nand exam-day workflows.",
        'colors' => [[2, 132, 199], [14, 165, 233], [125, 211, 252]],
        'icon' => 'exam',
    ],
    'question' => [
        'eyebrow' => 'Question bank',
        'title' => 'Practice with purpose.',
        'subtitle' => "Concept drills, difficulty levels,\nand clear explanations.",
        'colors' => [[5, 150, 105], [16, 185, 129], [110, 231, 183]],
        'icon' => 'question',
    ],
    'blog' => [
        'eyebrow' => 'Mentor blogs',
        'title' => 'Guides that stick.',
        'subtitle' => "Strategy, syllabus clarity,\nand revision frameworks.",
        'colors' => [[180, 83, 9], [234, 88, 12], [251, 146, 60]],
        'icon' => 'blog',
    ],
    'news' => [
        'eyebrow' => 'Education news',
        'title' => 'Stay ahead of deadlines.',
        'subtitle' => "Alerts, updates, and campus news\nfor serious aspirants.",
        'colors' => [[185, 28, 28], [220, 38, 38], [248, 113, 113]],
        'icon' => 'news',
    ],
    'organization' => [
        'eyebrow' => 'For institutes',
        'title' => 'Teach with structure.',
        'subtitle' => "Publish exams, media, and learning\npaths with confidence.",
        'colors' => [[15, 118, 110], [20, 184, 166], [94, 234, 212]],
        'icon' => 'org',
    ],
    'profile' => [
        'eyebrow' => 'Authors',
        'title' => 'Voices that mentor.',
        'subtitle' => "Meet the educators and creators\nbehind Examtube content.",
        'colors' => [[109, 40, 217], [124, 58, 237], [167, 139, 250]],
        'icon' => 'profile',
    ],
    'contact' => [
        'eyebrow' => 'Support',
        'title' => "We're here to help.",
        'subtitle' => "Reach the Examtube team for exams,\naccounts, and onboarding.",
        'colors' => [[3, 105, 161], [2, 132, 199], [56, 189, 248]],
        'icon' => 'contact',
    ],
    'about' => [
        'eyebrow' => 'Our story',
        'title' => 'More than exams.',
        'subtitle' => "A complete learning platform for\nstudents, mentors & institutes.",
        'colors' => [[15, 118, 110], [13, 148, 136], [45, 212, 191]],
        'icon' => 'about',
    ],
    'privacy' => [
        'eyebrow' => 'Legal',
        'title' => 'Your privacy matters.',
        'subtitle' => "How Examtube collects, uses,\nand protects your data.",
        'colors' => [[51, 65, 85], [71, 85, 105], [148, 163, 184]],
        'icon' => 'legal',
    ],
    'terms' => [
        'eyebrow' => 'Legal',
        'title' => 'Clear terms of use.',
        'subtitle' => "The rules that keep Examtube\nfair, safe, and trustworthy.",
        'colors' => [[30, 41, 59], [51, 65, 85], [100, 116, 139]],
        'icon' => 'legal',
    ],
];

$w = 1200;
$h = 630;

function rgba($img, int $r, int $g, int $b, int $a = 0)
{
    return imagecolorallocatealpha($img, $r, $g, $b, max(0, min(127, $a)));
}

function fill_vertical_gradient($img, int $w, int $h, array $c1, array $c2): void
{
    for ($y = 0; $y < $h; $y++) {
        $t = $y / max(1, $h - 1);
        $r = (int) round($c1[0] + ($c2[0] - $c1[0]) * $t);
        $g = (int) round($c1[1] + ($c2[1] - $c1[1]) * $t);
        $b = (int) round($c1[2] + ($c2[2] - $c1[2]) * $t);
        $col = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $col);
    }
}

function soft_circle($img, int $cx, int $cy, int $radius, array $rgb, int $alpha): void
{
    $col = rgba($img, $rgb[0], $rgb[1], $rgb[2], $alpha);
    imagefilledellipse($img, $cx, $cy, $radius * 2, $radius * 2, $col);
}

function rounded_rect($img, int $x1, int $y1, int $x2, int $y2, int $radius, $color): void
{
    imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
}

function draw_text_lines($img, string $font, float $size, int $x, int $y, string $text, $color, int $lineGap = 12): int
{
    $lines = preg_split("/\\r\\n|\\n|\\r/", $text) ?: [$text];
    $cursor = $y;
    foreach ($lines as $line) {
        imagettftext($img, $size, 0, $x, $cursor, $color, $font, $line);
        $box = imagettfbbox($size, 0, $font, $line);
        $lineH = abs($box[7] - $box[1]);
        $cursor += $lineH + $lineGap;
    }

    return $cursor;
}

function draw_icon_panel($img, string $icon, int $ox, int $oy, array $accent, $white, $panelBg): void
{
    // Glass card
    rounded_rect($img, $ox, $oy, $ox + 340, $oy + 340, 36, $panelBg);
    $ring = rgba($img, $accent[0], $accent[1], $accent[2], 70);
    imageellipse($img, $ox + 170, $oy + 170, 250, 250, $ring);
    imageellipse($img, $ox + 170, $oy + 170, 210, 210, $ring);

    $cx = $ox + 170;
    $cy = $oy + 170;
    $ink = rgba($img, 255, 255, 255, 10);

    switch ($icon) {
        case 'exam':
            // Clipboard
            rounded_rect($img, $cx - 70, $cy - 90, $cx + 70, $cy + 100, 18, $ink);
            rounded_rect($img, $cx - 36, $cy - 108, $cx + 36, $cy - 78, 10, $white);
            imageline($img, $cx - 42, $cy - 30, $cx + 42, $cy - 30, $white);
            imageline($img, $cx - 42, $cy + 5, $cx + 42, $cy + 5, $white);
            imageline($img, $cx - 42, $cy + 40, $cx + 20, $cy + 40, $white);
            break;
        case 'question':
            imagefilledellipse($img, $cx, $cy - 10, 120, 120, $ink);
            imagettftext($img, 64, 0, $cx - 18, $cy + 18, $white, 'C:/Windows/Fonts/segoeuib.ttf', '?');
            imagefilledellipse($img, $cx, $cy + 78, 16, 16, $white);
            break;
        case 'blog':
            rounded_rect($img, $cx - 80, $cy - 95, $cx + 80, $cy + 95, 16, $ink);
            imageline($img, $cx - 50, $cy - 50, $cx + 50, $cy - 50, $white);
            imageline($img, $cx - 50, $cy - 15, $cx + 50, $cy - 15, $white);
            imageline($img, $cx - 50, $cy + 20, $cx + 30, $cy + 20, $white);
            imageline($img, $cx - 50, $cy + 55, $cx + 45, $cy + 55, $white);
            break;
        case 'news':
            rounded_rect($img, $cx - 90, $cy - 80, $cx + 90, $cy + 90, 14, $ink);
            rounded_rect($img, $cx - 70, $cy - 55, $cx - 10, $cy + 10, 8, $white);
            imageline($img, $cx + 5, $cy - 45, $cx + 70, $cy - 45, $white);
            imageline($img, $cx + 5, $cy - 15, $cx + 70, $cy - 15, $white);
            imageline($img, $cx - 70, $cy + 40, $cx + 70, $cy + 40, $white);
            imageline($img, $cx - 70, $cy + 65, $cx + 40, $cy + 65, $white);
            break;
        case 'category':
            for ($i = 0; $i < 4; $i++) {
                $col = $i % 2;
                $row = intdiv($i, 2);
                $x1 = $cx - 75 + $col * 90;
                $y1 = $cy - 75 + $row * 90;
                rounded_rect($img, $x1, $y1, $x1 + 70, $y1 + 70, 14, $ink);
            }
            break;
        case 'profile':
            imagefilledellipse($img, $cx, $cy - 40, 90, 90, $ink);
            imagefilledellipse($img, $cx, $cy + 85, 150, 120, $ink);
            break;
        case 'contact':
            rounded_rect($img, $cx - 90, $cy - 60, $cx + 90, $cy + 60, 18, $ink);
            imageline($img, $cx - 90, $cy - 60, $cx, $cy + 10, $white);
            imageline($img, $cx + 90, $cy - 60, $cx, $cy + 10, $white);
            break;
        case 'legal':
            rounded_rect($img, $cx - 55, $cy - 100, $cx + 55, $cy + 100, 12, $ink);
            imageline($img, $cx - 30, $cy - 50, $cx + 30, $cy - 50, $white);
            imageline($img, $cx - 30, $cy - 10, $cx + 30, $cy - 10, $white);
            imageline($img, $cx - 30, $cy + 30, $cx + 20, $cy + 30, $white);
            break;
        case 'org':
            // Building
            rounded_rect($img, $cx - 80, $cy - 40, $cx + 80, $cy + 100, 10, $ink);
            rounded_rect($img, $cx - 35, $cy - 100, $cx + 35, $cy - 40, 10, $ink);
            for ($r = 0; $r < 3; $r++) {
                for ($c = 0; $c < 3; $c++) {
                    $wx = $cx - 55 + $c * 40;
                    $wy = $cy - 10 + $r * 35;
                    imagefilledrectangle($img, $wx, $wy, $wx + 18, $wy + 18, $white);
                }
            }
            break;
        case 'about':
        case 'home':
        default:
            // Abstract spark / star burst
            imagefilledellipse($img, $cx, $cy, 140, 140, $ink);
            for ($a = 0; $a < 8; $a++) {
                $ang = deg2rad($a * 45);
                $x2 = (int) round($cx + cos($ang) * 120);
                $y2 = (int) round($cy + sin($ang) * 120);
                imageline($img, $cx, $cy, $x2, $y2, $white);
            }
            imagefilledellipse($img, $cx, $cy, 48, 48, $white);
            break;
    }
}

foreach ($cards as $key => $meta) {
    $pngPath = $outDir.'/'.$key.'.png';
    if (! $force && is_file($pngPath)) {
        echo "Skip {$key} (curated PNG present; use --force to overwrite)\n";
        continue;
    }

    $img = imagecreatetruecolor($w, $h);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    $accent = $meta['colors'][0];
    $mid = $meta['colors'][1];
    $light = $meta['colors'][2];

    // Deep slate base → accent wash
    fill_vertical_gradient($img, $w, $h, [11, 18, 32], [17, 24, 39]);

    // Mesh orbs
    soft_circle($img, 980, 80, 260, $mid, 95);
    soft_circle($img, 1080, 420, 220, $accent, 105);
    soft_circle($img, -40, 520, 280, $light, 110);
    soft_circle($img, 420, -60, 180, $accent, 112);

    // Subtle dot grid
    $dot = rgba($img, 255, 255, 255, 118);
    for ($gx = 40; $gx < $w; $gx += 28) {
        for ($gy = 40; $gy < $h; $gy += 28) {
            if (($gx + $gy) % 56 === 0) {
                imagefilledellipse($img, $gx, $gy, 2, 2, $dot);
            }
        }
    }

    // Left glow panel
    $panel = rgba($img, 15, 23, 42, 55);
    rounded_rect($img, 48, 48, 700, 582, 28, $panel);

    $white = imagecolorallocate($img, 255, 255, 255);
    $muted = imagecolorallocate($img, 186, 198, 214);
    $accentCol = imagecolorallocate($img, $accent[0], $accent[1], $accent[2]);

    // Eyebrow pill
    $pillBg = rgba($img, $accent[0], $accent[1], $accent[2], 85);
    $eyebrow = strtoupper($meta['eyebrow']);
    $ebBox = imagettfbbox(15, 0, $fontSemi, $eyebrow);
    $ebW = abs($ebBox[2] - $ebBox[0]);
    rounded_rect($img, 88, 100, 88 + $ebW + 36, 140, 18, $pillBg);
    imagettftext($img, 15, 0, 106, 126, $white, $fontSemi, $eyebrow);

    // Title
    draw_text_lines($img, $fontBold, 54, 88, 230, $meta['title'], $white, 10);

    // Subtitle
    draw_text_lines($img, $fontRegular, 22, 88, 330, $meta['subtitle'], $muted, 10);

    // Accent underline bar under title area
    imagefilledrectangle($img, 88, 500, 188, 508, $accentCol);

    // Right illustration card
    $glass = rgba($img, 30, 41, 59, 40);
    draw_icon_panel($img, $meta['icon'], 780, 145, $light, $white, $glass);

    // Bottom-right brand only
    $brand = 'Examtube.in';
    $brandBox = imagettfbbox(18, 0, $fontSemi, $brand);
    $brandW = abs($brandBox[2] - $brandBox[0]);
    $brandX = $w - 56 - $brandW;
    $brandY = $h - 42;
    $brandCol = rgba($img, 226, 232, 240, 25);
    imagettftext($img, 18, 0, $brandX, $brandY, $brandCol, $fontSemi, $brand);

    // Tiny accent dot before brand
    imagefilledellipse($img, $brandX - 16, $brandY - 7, 8, 8, $accentCol);

    imagepng($img, $pngPath, 5);
    imagedestroy($img);

    echo "Generated {$key}\n";
}

echo "Done — SEO PNG defaults written to public/frontend/images/seo/\n";
