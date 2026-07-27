<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Ensures production-quality demo images exist under public/seed/.
 * Prefer existing files; generate polished JPEG/PNG assets when missing.
 */
class SeedAssetGenerator
{
    /**
     * Relative paths under public/seed that the demo media seeder expects.
     *
     * @return list<array{path:string,width:int,height:int,kind:string,label:string,palette:array{0:int,1:int,2:int}}>
     */
    public static function manifest(): array
    {
        $teal = [15, 118, 110];
        $navy = [15, 23, 42];
        $emerald = [6, 95, 70];
        $sky = [3, 105, 161];
        $indigo = [49, 46, 129];
        $slate = [51, 65, 85];
        $amber = [146, 64, 14];

        $heroes = [
            ['hero-01-exam-confidence.jpg', 'Master every exam', $teal],
            ['hero-02-practice-floor.jpg', 'Practice like exam day', $navy],
            ['hero-03-news-insights.jpg', 'Stay exam informed', $sky],
            ['hero-04-analytics.jpg', 'Track every attempt', $indigo],
            ['hero-05-career-ready.jpg', 'Get career ready', $emerald],
        ];

        $items = [
            ['path' => 'brand/logo.png', 'width' => 512, 'height' => 512, 'kind' => 'logo', 'label' => 'Examtube', 'palette' => $teal],
            ['path' => 'brand/favicon.png', 'width' => 64, 'height' => 64, 'kind' => 'favicon', 'label' => 'E', 'palette' => $teal],
            ['path' => 'brand/og-image.jpg', 'width' => 1200, 'height' => 630, 'kind' => 'banner', 'label' => 'Examtube.in — Practice smarter', 'palette' => $teal],
        ];

        foreach ($heroes as $i => $hero) {
            $items[] = [
                'path' => 'heroes/'.$hero[0],
                'width' => 1600,
                'height' => 900,
                'kind' => 'hero',
                'label' => $hero[1],
                'palette' => $hero[2],
            ];
            $items[] = [
                'path' => 'heroes/mobile-'.sprintf('%02d', $i + 1).'.jpg',
                'width' => 900,
                'height' => 1200,
                'kind' => 'hero',
                'label' => $hero[1],
                'palette' => $hero[2],
            ];
        }

        foreach ([
            ['partner-01-skillvista.png', 'SkillVista'],
            ['partner-02-campusbridge.png', 'CampusBridge'],
            ['partner-03-hireready.png', 'HireReady'],
            ['partner-04-edupulse.png', 'EduPulse'],
        ] as $i => $partner) {
            $items[] = [
                'path' => 'partners/'.$partner[0],
                'width' => 480,
                'height' => 240,
                'kind' => 'partner',
                'label' => $partner[1],
                'palette' => [$slate[0], $slate[1], max(40, $slate[2] - ($i * 12))],
            ];
        }

        foreach ([
            ['avatar-01-ananya.jpg', 'AS', [20, 83, 45]],
            ['avatar-02-rahul.jpg', 'RN', [30, 64, 175]],
            ['avatar-03-fatima.jpg', 'FK', [126, 34, 206]],
            ['avatar-04-vikram.jpg', 'VJ', [180, 83, 9]],
            ['avatar-user-appadmin.jpg', 'AA', [15, 118, 110]],
            ['avatar-user-orgadmin.jpg', 'OA', [3, 105, 161]],
            ['avatar-user-candidate.jpg', 'CA', [67, 56, 202]],
        ] as $avatar) {
            $items[] = [
                'path' => 'avatars/'.$avatar[0],
                'width' => 400,
                'height' => 400,
                'kind' => 'avatar',
                'label' => $avatar[1],
                'palette' => $avatar[2],
            ];
        }

        for ($i = 1; $i <= 12; $i++) {
            $palettes = [$teal, $navy, $emerald, $sky, $indigo, $amber];
            $items[] = [
                'path' => sprintf('exams/exam-banner-%02d.jpg', $i),
                'width' => 1200,
                'height' => 675,
                'kind' => 'banner',
                'label' => 'Interview Assessment '.$i,
                'palette' => $palettes[($i - 1) % count($palettes)],
            ];
        }

        foreach ([
            ['page-about.jpg', 'About Examtube', $teal],
            ['page-contact.jpg', 'Contact support', $sky],
            ['page-privacy.jpg', 'Privacy policy', $slate],
            ['page-terms.jpg', 'Terms of use', $navy],
            ['page-help.jpg', 'Help center', $emerald],
            ['page-careers.jpg', 'Careers', $indigo],
        ] as $page) {
            $items[] = [
                'path' => 'pages/'.$page[0],
                'width' => 1400,
                'height' => 560,
                'kind' => 'banner',
                'label' => $page[1],
                'palette' => $page[2],
            ];
        }

        foreach ([
            ['ad-home-sidebar.jpg', 'Premium mocks', $teal],
            ['ad-exam-list.jpg', 'Browse exams', $indigo],
            ['ad-blog-sidebar.jpg', 'Prep smarter', $sky],
            ['ad-news-sidebar.jpg', 'Campus alerts', $emerald],
            ['ad-exam-result.jpg', 'Retake & improve', $amber],
            ['ad-footer.jpg', 'Examtube premium', $navy],
        ] as $ad) {
            $items[] = [
                'path' => 'ads/'.$ad[0],
                'width' => 800,
                'height' => 400,
                'kind' => 'ad',
                'label' => $ad[1],
                'palette' => $ad[2],
            ];
        }

        for ($i = 1; $i <= 8; $i++) {
            $palettes = [$teal, $sky, $emerald, $indigo, $navy, $amber, $slate, [124, 45, 18]];
            $items[] = [
                'path' => sprintf('gallery/gallery-%02d.jpg', $i),
                'width' => 1200,
                'height' => 800,
                'kind' => 'gallery',
                'label' => 'Campus gallery '.$i,
                'palette' => $palettes[$i - 1],
            ];
        }

        return $items;
    }

    /**
     * Create any missing files under public/seed.
     *
     * @return array{created:int,skipped:int}
     */
    public function ensure(): array
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('GD extension is required to generate seed images.');
        }

        $root = public_path('seed');
        File::ensureDirectoryExists($root);

        $created = 0;
        $skipped = 0;

        foreach (self::manifest() as $item) {
            $absolute = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $item['path']);
            File::ensureDirectoryExists(dirname($absolute));

            if (is_file($absolute) && filesize($absolute) > 512) {
                $skipped++;

                continue;
            }

            $this->render($absolute, $item);
            $created++;
        }

        return compact('created', 'skipped');
    }

    /**
     * @param  array{path:string,width:int,height:int,kind:string,label:string,palette:array{0:int,1:int,2:int}}  $item
     */
    private function render(string $absolute, array $item): void
    {
        $width = (int) $item['width'];
        $height = (int) $item['height'];
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Unable to allocate image for '.$item['path']);
        }

        imagesavealpha($image, true);
        imagealphablending($image, true);

        [$r, $g, $b] = $item['palette'];
        $this->paintBackground($image, $width, $height, $r, $g, $b, $item['kind']);

        match ($item['kind']) {
            'logo' => $this->paintLogo($image, $width, $height, $item['label']),
            'favicon' => $this->paintFavicon($image, $width, $height, $item['label']),
            'avatar' => $this->paintAvatar($image, $width, $height, $item['label']),
            'partner' => $this->paintPartner($image, $width, $height, $item['label']),
            default => $this->paintBannerCopy($image, $width, $height, $item['label'], $item['kind']),
        };

        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        if ($extension === 'png') {
            imagepng($image, $absolute, 6);
        } else {
            imagejpeg($image, $absolute, 86);
        }

        imagedestroy($image);
    }

    /**
     * @param  \GdImage  $image
     */
    private function paintBackground($image, int $width, int $height, int $r, int $g, int $b, string $kind): void
    {
        for ($y = 0; $y < $height; $y++) {
            $t = $y / max(1, $height - 1);
            $rr = (int) max(0, min(255, $r + (($kind === 'avatar' ? 40 : 18) * (1 - $t)) - (12 * $t)));
            $gg = (int) max(0, min(255, $g + (($kind === 'avatar' ? 30 : 12) * (1 - $t)) - (8 * $t)));
            $bb = (int) max(0, min(255, $b + (($kind === 'avatar' ? 20 : 8) * (1 - $t)) + (18 * $t)));
            $color = imagecolorallocate($image, $rr, $gg, $bb);
            imageline($image, 0, $y, $width, $y, $color);
        }

        // Soft geometric accents for a professional editorial look.
        $accent = imagecolorallocatealpha($image, 255, 255, 255, 100);
        $accent2 = imagecolorallocatealpha($image, 255, 255, 255, 115);
        imagefilledellipse($image, (int) ($width * 0.82), (int) ($height * 0.18), (int) ($width * 0.45), (int) ($height * 0.55), $accent);
        imagefilledellipse($image, (int) ($width * 0.12), (int) ($height * 0.88), (int) ($width * 0.35), (int) ($height * 0.45), $accent2);

        if (in_array($kind, ['hero', 'banner', 'ad', 'gallery'], true)) {
            $panel = imagecolorallocatealpha($image, 15, 23, 42, 70);
            imagefilledrectangle($image, 0, (int) ($height * 0.58), $width, $height, $panel);
        }
    }

    /**
     * @param  \GdImage  $image
     */
    private function paintLogo($image, int $width, int $height, string $label): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $disk = imagecolorallocatealpha($image, 255, 255, 255, 95);
        imagefilledellipse($image, (int) ($width / 2), (int) ($height / 2), (int) ($width * 0.72), (int) ($height * 0.72), $disk);
        $this->drawCenteredString($image, $label, $white, (int) ($width / 2), (int) ($height / 2) - 8, 5);
        $this->drawCenteredString($image, 'IN', $white, (int) ($width / 2), (int) ($height / 2) + 28, 3);
    }

    /**
     * @param  \GdImage  $image
     */
    private function paintFavicon($image, int $width, int $height, string $label): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $this->drawCenteredString($image, $label, $white, (int) ($width / 2), (int) ($height / 2) - 6, 5);
    }

    /**
     * @param  \GdImage  $image
     */
    private function paintAvatar($image, int $width, int $height, string $label): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $ring = imagecolorallocatealpha($image, 255, 255, 255, 90);
        imagefilledellipse($image, (int) ($width / 2), (int) ($height / 2), (int) ($width * 0.86), (int) ($height * 0.86), $ring);
        $this->drawCenteredString($image, $label, $white, (int) ($width / 2), (int) ($height / 2) - 8, 5);
    }

    /**
     * @param  \GdImage  $image
     */
    private function paintPartner($image, int $width, int $height, string $label): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $panel = imagecolorallocatealpha($image, 255, 255, 255, 105);
        imagefilledrectangle($image, 24, 24, $width - 24, $height - 24, $panel);
        $this->drawCenteredString($image, $label, $white, (int) ($width / 2), (int) ($height / 2) - 6, 5);
    }

    /**
     * @param  \GdImage  $image
     */
    private function paintBannerCopy($image, int $width, int $height, string $label, string $kind): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocate($image, 226, 232, 240);
        $eyebrow = match ($kind) {
            'hero' => 'EXAMTUBE.IN',
            'ad' => 'SPONSORED',
            'gallery' => 'GALLERY',
            default => 'EXAMTUBE',
        };

        imagestring($image, 3, 48, (int) ($height * 0.64), $eyebrow, $muted);
        imagestring($image, 5, 48, (int) ($height * 0.72), $this->fitLabel($label, 46), $white);
        imagestring($image, 3, 48, (int) ($height * 0.82), 'Mock tests · Insights · Career prep', $muted);
    }

    /**
     * @param  \GdImage  $image
     */
    private function drawCenteredString($image, string $text, int $color, int $cx, int $cy, int $font): void
    {
        $text = $this->fitLabel($text, 40);
        $width = imagefontwidth($font) * strlen($text);
        $height = imagefontheight($font);
        imagestring($image, $font, (int) ($cx - ($width / 2)), (int) ($cy - ($height / 2)), $text, $color);
    }

    private function fitLabel(string $label, int $max): string
    {
        $label = trim($label);
        if (strlen($label) <= $max) {
            return $label;
        }

        return substr($label, 0, $max - 1).'…';
    }
}
