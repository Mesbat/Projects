<?php
function getname_e($link){
	preg_match("/([^\/]*)$/", $link, $ext);
	if (strlen($ext[1]) > 15)
		$ret = substr($ext[1], 0, 6) . "[...]" . substr($ext[1], -4);
	else
		$ret = $ext[1];
	return ($ret);
}

function getname($link){
	preg_match("/([^\/]*)[.][jpgpnif]+$/", $link, $ext);
	if (strlen($ext[1]) > 15)
		$ret = substr($ext[1], 0, 10) . "[...]"; 
	else
		$ret = $ext[1];
	return ($ret);
}

function sorturl($tab) {
	$i = 0;
	while ($tab[$i + 1]) {
		preg_match("/([^\/]*)[.][jpgfni]+$/", $tab[$i], $ext);
		preg_match("/([^\/]*)[.][jpgfni]+$/", $tab[$i + 1], $ext2);
		if (strcmp(strtolower($ext[1]), strtolower($ext2[1])) > 0) {
			$rep = $tab[$i];
			$tab[$i] = $tab[$i + 1];
			$tab[$i + 1] = $rep;
			$i = 0;
		}
		++$i;
	}
	return ($tab);
}

function urlexists($url) {
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_NOBODY, true);
	$result = curl_exec($curl);
	if ($result !== false) 
	{
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);  
		if ($statusCode == 404) 
		{
			return (0);
		}
		else if ($statusCode == 302 || $statusCode == 200 ) 
		{
			return (1);
		} 
	}
	else
	{
		return (0);
	}
}

function checkerror($argv, $argc) { 
	global $Options, $maximg;
	$filename = array();
	$i = 1;
	while ($i != $argc - 1) {
		if (urlexists($argv[$i]))
			$file_headers = "HTTP 302 Found";
		else 
			$file_headers = "HTTP 404 Not Found";
		if (substr($argv[$i], 0, 1) == "-" && $i == 1) {
			if (!preg_match("/^-[gjlnNps]+$/", $argv[$i])) {
				echo "\033[31mUsage : php imagepanel.php [-options] lien1 [lien2 [...]] base\033[0m\nOptions : \n[g] La ou les images générées doivent être en GIF (.GIF ou .gif)\n[j] La ou les images générées doivent être en JPEG (.JPEG, .jpeg, .JPG ou .jpg)\n[l] L'argument suivant est le nombre maximum d'images incrustées dans la méta-image\n[n] Afficher sous les images le nom de celles-ci (sans l'extension)\n[N] Afficher sous les images le nom de celles-ci (avec l'extension)\n[p] La ou les images générées doivent être en PNG (.PNG ou .png)\n[s] Trier les images par ordre alphabétique\n";
				return (0);
			}
			if (stripos($argv[$i], "l")) {
				if (intval($argv[2]) <= 0 || $argc < 5) {
					echo "\033[31mUsage : php imagepanel.php [-options] lien1 [lien2 [...]] base\033[0m\nOptions : \n[g] La ou les images générées doivent être en GIF (.GIF ou .gif)\n[j] La ou les images générées doivent être en JPEG (.JPEG, .jpeg, .JPG ou .jpg)\n[l] L'argument suivant est le nombre maximum d'images incrustées dans la méta-image\n[n] Afficher sous les images le nom de celles-ci (sans l'extension)\n[N] Afficher sous les images le nom de celles-ci (avec l'extension)\n[p] La ou les images générées doivent être en PNG (.PNG ou .png)\n[s] Trier les images par ordre alphabétique\n";
					return (0);
				}
				$maximg = round($argv[$i + 1]);
				$Options = $argv[$i];
				++$i;
				$a = 2;
			}
			else {
				$a = 1;
				$maximg = -1;
				$Options = $argv[$i];
			}
			if ($maximg > 0)
				echo "\033[36mOptions \033[0m: " . $Options . "\033[36m Nombre d'images max \033[0m: " . $maximg . "\n";
			else
				echo "\033[36mOptions \033[0m: " . $Options . "\033[36m Nombre d'images max \033[0m: inf.\n";
		}
		else if ((stripos($file_headers, "302 Found") > 0) || (stripos($file_headers, "200 OK") > 0) || (stripos($argv[$i], ".html") > 0) && (file_exists($argv[$i]) == true) || (stripos($argv[$i], ".php") > 0) && (file_exists($argv[$i]) == true)) {
			echo " | Le lien \033[32mnuméro ". (intval($i) - intval($a)) . "\033[0m est valide. -> \033[32m 302 Found.\033[0m\n";
			$filename[] = $argv[$i];
			$ok = 1;
		}
		else {
			echo " | Le lien \033[31mnuméro ". (intval($i) - intval($a)) . " \033[0m est invalide. -> \033[31m 404 Not Found.\033[0m\n";
		}
		++$i;
	}
	return ($filename);
}

function get_images($filename) {
	$allsrc = [];
	for ($i = 0; isset($filename[$i]); ++$i) {
		$file = fopen($filename[$i], "r");
		$code = "";
		while (!feof($file)) {
			$code .= fread($file, 8192);
		}
		preg_match_all("/<img[^>]*src=\"([^\"]*)\"[^>]*>/i", $code, $src);

		preg_match("/[^\/]*\/\/[^\/]*/", $filename[$i], $root);
		$root = preg_replace("/https:\/\//", "http://", $root[0]);

		for ($j = 0; isset($src[1][$j]); $j++) {
			if (!preg_match("/background|bg_|_bg|_bg_/", $src[1][$j]))
				$allsrc[count($allsrc)] = $src[1][$j];
			if (preg_match("/\A\/[^\/]/", $allsrc[count($allsrc) - 1]))
				$allsrc[count($allsrc) - 1] = preg_replace("/\A\/([^\/])/", $root . "/$1", $allsrc[count($allsrc) - 1]); // Le lien de l'image commence par '/'
			else if (preg_match("/\A[^\/]/", $allsrc[count($allsrc) - 1]) && !preg_match("/\Ahttps?:\/\//", $allsrc[count($allsrc) - 1])) {
				if (substr_count($filename[$i], '//') == 1 && substr_count($filename[$i], '/') == 2)
					$filename[$i] .= '/';
				preg_match("/.*\//", $filename[$i], $tmp);
				$allsrc[count($allsrc) - 1] = $tmp[0] . $allsrc[count($allsrc) - 1];
			}
		}
		fclose($file);
	}
	return $allsrc;
}
?>