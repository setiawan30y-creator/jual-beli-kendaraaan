<?php

namespace Database\Seeders;

/** Membuat ilustrasi PNG sederhana untuk data demo (tanpa aset berhak cipta). Membutuhkan ekstensi GD. */
class DemoImage
{
    public static function vehicle(string $category, string $label, string $color, int $angle, int $seed): string
    {
        $w = 800;
        $h = 600;
        $img = imagecreatetruecolor($w, $h);
        $palettes = [[226, 236, 252], [232, 245, 238], [252, 240, 228], [238, 234, 250], [240, 242, 245]];
        [$r, $g, $b] = $palettes[$seed % count($palettes)];
        for ($y = 0; $y < $h; $y++) {
            $f = $y / $h;
            $c = imagecolorallocate($img, (int) ($r - 18 * $f), (int) ($g - 18 * $f), (int) ($b - 18 * $f));
            imageline($img, 0, $y, $w, $y, $c);
        }
        $floor = imagecolorallocate($img, (int) ($r - 40), (int) ($g - 40), (int) ($b - 40));
        imagefilledellipse($img, 400, 470, 620, 60, $floor);
        $bodyColors = ['Hitam' => [40, 44, 52], 'Putih' => [245, 245, 245], 'Merah' => [196, 43, 43], 'Hijau' => [30, 130, 76],
            'Krem' => [222, 205, 170], 'Biru muda' => [120, 180, 230], 'Silver' => [180, 186, 194], 'Kuning' => [235, 190, 40], 'Biru' => [40, 90, 190], 'Abu-abu' => [120, 126, 136]];
        [$br, $bg, $bb] = $bodyColors[$color] ?? [60, 90, 160];
        $body = imagecolorallocate($img, $br, $bg, $bb);
        $dark = imagecolorallocate($img, 30, 32, 38);
        $rim = imagecolorallocate($img, 190, 194, 200);
        $glass = imagecolorallocate($img, 150, 190, 220);
        imagesetthickness($img, 4);

        if ($category === 'mobil') {
            if ($angle % 2 === 1) { // samping
                imagefilledrectangle($img, 140, 300, 660, 410, $body);
                imagefilledpolygon($img, [230, 300, 300, 220, 520, 220, 590, 300], $body);
                imagefilledpolygon($img, [255, 298, 312, 236, 400, 236, 400, 298], $glass);
                imagefilledpolygon($img, [415, 298, 415, 236, 510, 236, 565, 298], $glass);
                foreach ([250, 550] as $x) { imagefilledellipse($img, $x, 420, 110, 110, $dark); imagefilledellipse($img, $x, 420, 56, 56, $rim); }
            } else { // depan/belakang
                imagefilledrectangle($img, 220, 260, 580, 420, $body);
                imagefilledpolygon($img, [260, 260, 300, 190, 500, 190, 540, 260], $body);
                imagefilledpolygon($img, [285, 255, 315, 205, 485, 205, 515, 255], $glass);
                imagefilledrectangle($img, 230, 410, 290, 450, $dark);
                imagefilledrectangle($img, 510, 410, 570, 450, $dark);
                $lamp = imagecolorallocate($img, $angle === 0 ? 250 : 220, $angle === 0 ? 240 : 60, $angle === 0 ? 200 : 60);
                imagefilledellipse($img, 265, 300, 50, 26, $lamp);
                imagefilledellipse($img, 535, 300, 50, 26, $lamp);
            }
        } else {
            if ($angle % 2 === 1) {
                foreach ([240, 560] as $x) { imagefilledellipse($img, $x, 410, 150, 150, $dark); imagefilledellipse($img, $x, 410, 80, 80, $rim); }
                imagefilledpolygon($img, [250, 400, 330, 290, 470, 280, 560, 400, 470, 360, 330, 370], $body);
                imagefilledpolygon($img, [330, 290, 360, 250, 470, 250, 470, 285], $dark);
                imageline($img, 520, 300, 560, 410, $dark);
                imagefilledrectangle($img, 480, 230, 540, 245, $dark);
            } else {
                imagefilledellipse($img, 400, 420, 80, 150, $dark);
                imagefilledpolygon($img, [340, 380, 360, 230, 440, 230, 460, 380], $body);
                imagefilledrectangle($img, 300, 225, 500, 240, $dark);
                $lamp = imagecolorallocate($img, $angle === 0 ? 250 : 220, $angle === 0 ? 240 : 60, $angle === 0 ? 200 : 60);
                imagefilledellipse($img, 400, 280, 60, 40, $lamp);
            }
        }
        $text = imagecolorallocate($img, 70, 76, 90);
        imagestring($img, 5, 24, 24, strtoupper($label), $text);
        imagestring($img, 3, 24, 48, 'Foto ilustrasi demo', $text);

        ob_start();
        imagepng($img, null, 6);
        imagedestroy($img);

        return (string) ob_get_clean();
    }
}
