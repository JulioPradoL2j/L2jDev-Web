<?php
session_start();

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$w = 170; $h = 52;
$img = imagecreatetruecolor($w, $h);

$bg   = imagecolorallocate($img, 12, 16, 28);
$fg   = imagecolorallocate($img, 235, 240, 255);
$acc  = imagecolorallocate($img, 255, 160, 60);
$line = imagecolorallocate($img, 60, 80, 140);

imagefilledrectangle($img, 0, 0, $w, $h, $bg);

for ($i=0; $i<7; $i++) {
    imageline($img, rand(0,$w), rand(0,$h), rand(0,$w), rand(0,$h), $line);
}
for ($i=0; $i<120; $i++) {
    imagesetpixel($img, rand(0,$w-1), rand(0,$h-1), $line);
}

$a = rand(2, 9);
$b = rand(1, 9);
$op = rand(0,1) ? '+' : '-';
if ($op === '-' && $b > $a) { $t=$a; $a=$b; $b=$t; }

$question = "{$a} {$op} {$b}";
$answer   = ($op === '+') ? ($a + $b) : ($a - $b);

$_SESSION['captcha_answer'] = (string)$answer;
$_SESSION['captcha_time']   = time();

imagestring($img, 5, 18, 16, "Quanto e: ", $fg);
imagestring($img, 5, 98, 16, $question, $acc);

imagepng($img);
imagedestroy($img);
