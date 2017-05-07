<?php

	$width = 32;
	$height = 34;
	$p = urldecode($_GET["p"]);

	$output_cols = 4;
	if (isset($_GET["columns"])) {
		$output_cols = $_GET["columns"];
	}
	
	$smileys = array(
		"<3", ";)", "x(", ":$", "L)", "=;", 
		"</3", ":)", ":(", ":/", "|)", ":|",
	    "%)", ":D", ":B", ":@", "#0", ":#",
	    "8)", ":P", "8|", "@)", ":O", ":&",
	    ":>", ":v", "8}", ":*");
	
	$index = 0;
	foreach ($smileys as $smiley) {
		$p = str_replace($smiley, "[!img$index]", $p);
		$index++;
	}
	
	$matches = array();
	$p = preg_replace_callback("/\[\!img([^\]]+)\]/",
		function ($match) {
			global $matches;
			$matches[] = $match[1];
		}, $p);
	
	$img_smileys = imagecreatefrompng("smileys.png");
	$rows = ceil(count($matches) / $output_cols);
	$img_smiley = imagecreate($width * $output_cols, $height * $rows);
	//$color = imagecolorallocate($img_smiley, 255, 255, 255);
	//imagefilledrectangle($img_smiley, 0, 0, $width, $height, $color);
	
	$index = 0;
	foreach ($matches as $match) {
		$dst_row = floor($index / $output_cols);
		$dst_col = $index % $output_cols;
		$src_row = floor($match / 6);
		$src_col = $match - $src_row * 6;
		imagecopy($img_smiley, $img_smileys,
			$width * $dst_col, $height * $dst_row, // dst
			$width * $src_col, $height * $src_row, // src
			$width, $height);
		$index++;
	}
	header('Content-Type: image/png');
	imagepng($img_smiley);
?>