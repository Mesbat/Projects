<?php
echo "\033[36m------------------------------ ImagePanel ------------------------------\033[0m\n";

require_once('funcs.php');

if ($argv[1][0] == "-")
	$i = 4;
else
	$i = 3;
if ($argc < $i) {
	echo "\033[31mUsage : php imagepanel.php [-options] lien1 [lien2 [...]] base\033[0m\nOptions : \n[g] La ou les images générées doivent être en GIF (.GIF ou .gif)\n[j] La ou les images générées doivent être en JPEG (.JPEG, .jpeg, .JPG ou .jpg)\n[l] L'argument suivant est le nombre maximum d'images incrustées dans la méta-image\n[n] Afficher sous les images le nom de celles-ci (sans l'extension)\n[N] Afficher sous les images le nom de celles-ci (avec l'extension)\n[p] La ou les images générées doivent être en PNG (.PNG ou .png)\n[s] Trier les images par ordre alphabétique\n";
	return (0);
}

echo "\033[36mFile name : ". $argv[$argc - 1] . "\033[0m\n";

$Options = 0;
$maximg = -1;

$filename = checkerror($argv, $argc);

if (empty($filename)) {
	echo "\033[31mAucun lien n'est valide.\033[0m\n";
	echo "\033[36m------------------------------------------------------------------------\033[0m\n";
	return (0);
}

else {
	$allsrc = get_images($filename);

	if (strpos($Options, "s")) {
		$allsrc = sorturl($allsrc);
	}

	$n = 0;
	$nb = 0;
	$j = 0;
	$pc = 0;
	if ($maximg <= 0)
		$maximg = count($allsrc);
	do {
		$height = 0;
		$width = 0;

		$finalw = ($maximg >= 10 ? 1000 : 100 * $maximg);
		if (strpos($Options, "n") || strpos($Options, "N"))
			$finalh = floor(($maximg - 1) / 10) * 120 + 120;
		else
			$finalh = floor(($maximg - 1) / 10) * 100 + 100;

		$dest = imagecreatetruecolor($finalw, $finalh);
		$white = imagecolorallocate($dest, 255, 255, 255);
		imagefill($dest, 0, 0, $white);

		for ($i = 0; isset($allsrc[$j]) && $i < $maximg; $j++, $i++) {
			$empty = 1;
			preg_match("/.*\.([^?]*)\??[^?]*/", $allsrc[$j], $ext);
			$ext = $ext[1];

			preg_match("/([^\/]*)\z/", $allsrc[$j], $base);
			$base = $base[1];

			$ext = preg_replace("/jpg\z/", "jpeg", $ext);
			$func = "imagecreatefrom" . $ext;
			if (($link = preg_replace("/\A\/\//", "http://", $allsrc[$j])) !== $allsrc[$j])
				$img = function_exists($func) ? $func($link) : FALSE;
			else {
				$link = $allsrc[$j];
				$img = function_exists($func) ? $func($link) : FALSE;
			}
			if ($img !== FALSE) {
				$max = imagesx($img) > imagesy($img) ? imagesx($img) : imagesy($img);
				if (strpos($Options, "n") || strpos($Options, "N")) {
					$black = ImageColorAllocate($img, 0, 0, 0);
					$func = (strpos($Options, "N") ? "getname_e" : "getname");
					Imagettftext($dest, 8, 0, ($width + 5), ($height + 112), $black, 'CaviarDreams.ttf', $func($allsrc[$j]));
				}
				imagecopyresampled($dest, $img, $width, $height, 0, 0, (imagesx($img) * 100) / $max, (imagesy($img) * 100) / $max, imagesx($img), imagesy($img));
				$empty = 0;
				if ($width > 800) {
					$width = 0;
					$height += 100;
					if (strpos($Options, "n") || strpos($Options, "N"))
						$height += 20;
				}
				else
					$width += 100;
				imagedestroy($img);
				$nb++;
			}
			$pc += 100 / count($allsrc);
			echo "\r" . round($pc) . "%  \033[D\033[D";
		}
		if ($empty == 0) {
			$name = $argv[$argc - 1] . ($n == 0 ? "" : $n + 1);
			if (strpos($Options, "j"))
				imagejpeg($dest, $name . ".jpg");
			if (strpos($Options, "p"))
				imagepng($dest, $name . ".png");
			if (strpos($Options, "g"))
				imagegif($dest, $name . ".gif");
			if (!strpos($Options, "j") && !strpos($Options, "p") && !strpos($Options, "g"))
				imagejpeg($dest, $name . ".jpg");
		}
		imagedestroy($dest);
		$n++;
	} while (isset($allsrc[$j]));
	echo "\r100%\n";
	echo $nb . " Images trouvées.\n";
}

echo "\033[36m------------------------------------------------------------------------\033[0m\n";
?>