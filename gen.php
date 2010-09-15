<?php
//test
set_time_limit(0);
if(isset($_GET['v'])){ $v = true; } else{ $v = false; }
$file_pre = "galaxys/" . $_GET['file'];
if($v) echo "Using file $file_pre<br>";
$file_otsu = str_replace(".tiff",".gif","temp/otsu" . $_GET['file']);
$file_use = str_replace(".tiff",".gif","temp/use" . $_GET['file']);



$exec = "convert -type Grayscale $file_pre $file_use";
if($v) echo "Converting tiff to gif<br>";
exec($exec, $yaks);
if($v) echo "<img src=\"$file_use\"><br>";
$exec = "./otsuthresh.sh $file_use $file_otsu";
if($v) echo "Generating Ostu Threashold and creating Binomial Mask<br>";
exec($exec);
$galaxy = LoadGif($file_use);
$galaxy_otsu = LoadGif($file_otsu);
$galaxyinfo = getimagesize($file_use);
if($v){
	$blended_otsu = imagecreatetruecolor($galaxyinfo[0],$galaxyinfo[1]);
	imagecopy($blended_otsu,$galaxy,0,0,0,0,$galaxyinfo[0],$galaxyinfo[1]);
	imagecopymerge($blended_otsu,$galaxy_otsu,0,0,0,0,$galaxyinfo[0],$galaxyinfo[1],50);
	$file_blended_otsu = str_replace(".tiff",".jpg","temp/blendotsu" . $_GET['file']);
	imagejpeg($blended_otsu,$file_blended_otsu);
	echo "<img src=\"$file_blended_otsu\"><br>";
	}

$found[] = array(-1,-1);
if($v) echo "Finding All Points<br>";
findall($galaxy_otsu,60,61,floor($galaxyinfo[0]/2),floor($galaxyinfo[1]/2));
if($v){
	$foundimage = imagecreatetruecolor($galaxyinfo[0],$galaxyinfo[1]);
	imagecopy($foundimage,$galaxy,0,0,0,0,$galaxyinfo[0],$galaxyinfo[1]);
	foreach($found as $points){
			imagesetpixel($foundimage,$points[0],$points[1],imagecolorallocate($foundimage,255,255,255));
		}
	$foundimage_file = str_replace(".tiff",".jpg","temp/foundimage" . $_GET['file']);
	imagejpeg($foundimage,$foundimage_file);
	echo "<img src=\"$foundimage_file\"><br>";
	}
unset($found[0]);
$found = array_values($found);
if($v) echo "Finding Middle and distance around.<br>";
$distanctinfo =  findinfo();
if($v){
	$infopoints = imagecreatetruecolor($galaxyinfo[0],$galaxyinfo[1]);
	imagecopy($infopoints,$galaxy,0,0,0,0,$galaxyinfo[0],$galaxyinfo[1]);
	imagesetpixel($infopoints,$distanctinfo[1],$distanctinfo[2],imagecolorallocate($infopoints,255,255,255));
	for($i = 0;$i <= 360; $i++){
		$outx = $distanctinfo[1] + $distanctinfo[0]*(cos(($i/180)*3.141592));
		$outy = $distanctinfo[2] + $distanctinfo[0]*(sin(($i/180)*3.141592));
		imagesetpixel($infopoints,$outx,$outy,imagecolorallocate($infopoints,255,255,255));
	}
	$infopoints_file = str_replace(".tiff",".jpg","temp/infopoints" . $_GET['file']);
	imagejpeg($infopoints,$infopoints_file);
	echo "<img src=\"$infopoints_file\"><br>";
}




if($v) echo "Grab the Circle at 100% edge.<br>";
$circlefull = lineSmooth(makecircle($galaxy,$distanctinfo[1],$distanctinfo[2],$distanctinfo[0]));
if($v){
	$graphfull = imagecreatetruecolor(361,255);
	$i = 0;
	foreach($circlefull as $pixel){
		imagesetpixel($graphfull,$i,255 - $pixel,imagecolorallocate($graphfull,255,255,255));
		$i++;
	}
	$graphfull_file = str_replace(".tiff",".jpg","temp/graphfull" . $_GET['file']);
	imagejpeg($graphfull,$graphfull_file);
	echo "<img src=\"$graphfull_file\"><br>";
}

if($v) echo "Grab the Circle at 75% edge.<br>";
$circle75 = lineSmooth(makecircle($galaxy,$distanctinfo[1],$distanctinfo[2],$distanctinfo[0]*0.75));
if($v){
	$graph75 = imagecreatetruecolor(361,255);
	$i = 0;
	foreach($circle75 as $pixel){
		imagesetpixel($graph75,$i,255 - $pixel,imagecolorallocate($graph75,255,255,255));
		$i++;
	}
	$graph75_file = str_replace(".tiff",".jpg","temp/graph75" . $_GET['file']);
	imagejpeg($graph75,$graph75_file);
	echo "<img src=\"$graph75_file\"><br>";
}

if($v) echo "Grab the Circle at 50% edge.<br>";
$circle50 = lineSmooth(makecircle($galaxy,$distanctinfo[1],$distanctinfo[2],$distanctinfo[0]*0.50));
if($v){
	$graph50 = imagecreatetruecolor(361,255);
	$i = 0;
	foreach($circle50 as $pixel){
		imagesetpixel($graph50,$i,255 - $pixel,imagecolorallocate($graph50,255,255,255));
		$i++;
	}
	$graph50_file = str_replace(".tiff",".jpg","temp/graph50" . $_GET['file']);
	imagejpeg($graph50,$graph50_file);
	echo "<img src=\"$graph50_file\"><br>";
}
if($v) echo "Grab the Circle at 25% edge.<br>";
$circle25 = lineSmooth(makecircle($galaxy,$distanctinfo[1],$distanctinfo[2],$distanctinfo[0]*0.25));
if($v){
	$graph25 = imagecreatetruecolor(361,255);
	$i = 0;
	foreach($circle25 as $pixel){
		imagesetpixel($graph25,$i,255 - $pixel,imagecolorallocate($graph25,255,255,255));
		$i++;
	}
	$graph25_file = str_replace(".tiff",".jpg","temp/graph25" . $_GET['file']);
	imagejpeg($graph25,$graph25_file);
	echo "<img src=\"$graph25_file\"><br>";
}

echo "Done";
//Fuctions



function LoadJpeg($imgname)
{
    /* Attempt to open */
    $im = @imagecreatefromjpeg($imgname);

    /* See if it failed */
    if(!$im)
    {
        /* Create a black image */
        $im  = imagecreatetruecolor(150, 30);
        $bgc = imagecolorallocate($im, 255, 255, 255);
        $tc  = imagecolorallocate($im, 0, 0, 0);

        imagefilledrectangle($im, 0, 0, 150, 30, $bgc);

        /* Output an error message */
        imagestring($im, 1, 5, 5, 'Error loading ' . $imgname, $tc);
    }

    return $im;
}
function LoadGif($imgname)
{
    /* Attempt to open */
    $im = @imagecreatefromgif($imgname);

    /* See if it failed */
    if(!$im)
    {
        /* Create a black image */
        $im  = imagecreatetruecolor(150, 30);
        $bgc = imagecolorallocate($im, 255, 255, 255);
        $tc  = imagecolorallocate($im, 0, 0, 0);

        imagefilledrectangle($im, 0, 0, 150, 30, $bgc);

        /* Output an error message */
        imagestring($im, 1, 5, 5, 'Error loading ' . $imgname, $tc);
    }

    return $im;
}
function lineSmooth($points){
	for($i = 0;$i <= 360; $i++){
		if($i == 0){ $less1 = 360; $less2 = 359;}
		elseif($i == 1){ $less1 = 0; $less2 = 360; }
		else{ $less1 = $i + 1; $less2 = $i - 2;}
		if($i == 360){ $more1 = 0; $more2 = 1;}
		elseif($i == 359){ $more1 = 360; $more2 = 359; }
		else{ $more1 = $i + 1; $more2 = $i + 2;}
		$roughavg = ($points[$i] + $points[$more1] + $points[$more2] + $points[$less1] + $points[$less2]) / 5;
		$tempsum = 0;
		$addedcount = 0;
		if(abs($points[$i] - $roughavg) < 20){ $tempsum += $points[$i]; $addedcount++; }
		if(abs($points[$less2] - $roughavg) < 20){ $tempsum += $points[$less2]; $addedcount++; }
		if(abs($points[$less1] - $roughavg) < 20){ $tempsum += $points[$less1]; $addedcount++; }
		if(abs($points[$more1] - $roughavg) < 20){ $tempsum += $points[$more1]; $addedcount++; }
		if(abs($points[$more2] - $roughavg) < 20){ $tempsum += $points[$more2]; $addedcount++; }
		@$smoothed[] = $tempsum / $addedcount;
	}
	return $smoothed;
}
function getBrightness($im, $x, $y){
	$rgb = imagecolorat($im, $x, $y);
	return $rgb;
}

function makecircle($im,$x,$y,$dis){
	for($i = 0;$i <= 360; $i++){
		$outx = $x + $dis*(cos(($i/180)*3.141592));
		$outy = $y + $dis*(sin(($i/180)*3.141592));
		$rgb = imagecolorat($im, $outx, $outy);
		$colors = imagecolorsforindex($im, $rgb);
		$array[] = $colors['red'];
	}
	return $array;
}
function findmid(){
	global $found;
	$first = true;
	foreach($found as $point){
		if($left > $point[0] or $first) $left = $point[0]; 
		if($point[0] > $right or $first) $right = $point[0];
		if($top > $point[1] or $first) $top = $point[1];
		if($point[1] > $bottom or $first) $bottom = $point[1];
		$first = false;
	}
	$temp = array(floor(($right + $left)/2),floor(($bottom + $top)/2));
	return $temp;
}

function findinfo(){
	global $found;
	$mids = findmid();
	$x = $mids[0];
	$y = $mids[1];
	$distance_old = 0;
	foreach($found as $point){
		if($point[0] != $x and $point[1] != $y){
			$distance_cur = sqrt(pow(($point[0] - $x),2) + pow(($point[1] - $y),2));
			if($distance_cur > $distance_old){ $distance_old = $distance_cur;}
		}
	}
	return array($distance_old,$x,$y);
}
/*
function findall($im,$x,$y){
	$checkon = false;
	global $found;
	if(getBrightness($im, $x, $y) == 1){
		if($checkon){ echo "<br>$x,$y on ";}
		$need[] = array($x,$y);
		$on = true;
			while($on){
			$x = $need[0][0];
			$y = $need[0][1];
			$found[] = $need[0];
			unset($need[0]);
			$need = array_values($need);
			if(getBrightness($im, $x - 1, $y) == 1){
				if(!in_array(array($x - 1,$y),$found)){
					$need[] = array($x - 1,$y);
				}
			}
			if(getBrightness($im, $x + 1, $y) == 1){
				if(!in_array(array($x + 1,$y),$found)){
					$need[] = array($x + 1,$y);
				}
			}
			if(getBrightness($im, $x, $y + 1) == 1){
				if(!in_array(array($x,$y + 1),$found)){
					$need[] = array($x,$y + 1);
				}
			}
			if(getBrightness($im, $x, $y - 1) == 1){
				if(!in_array(array($x,$y - 1),$found)){
					$need[] = array($x,$y - 1);
				}
			}
			if(count($need) == 0) $on = false;
		}
	}
}
*/


function findall($im,$x,$y){
	$checkon = false;
	global $found;
	if(getBrightness($im, $x, $y) == 1){
		if($checkon){ echo "<br>$x,$y on ";}
		$found[] = array($x,$y);
		if(getBrightness($im, $x - 1, $y) == 1){
			if(!in_array(array($x - 1,$y),$found)){
				findall($im, $x - 1, $y);
			}
		}
		if(getBrightness($im, $x + 1, $y) == 1){
			if(!in_array(array($x + 1,$y),$found)){
				findall($im, $x + 1, $y);
			}
		}
		if(getBrightness($im, $x, $y + 1) == 1){
			if(!in_array(array($x,$y + 1),$found)){
				findall($im, $x, $y + 1);
			}
		}
		if(getBrightness($im, $x, $y - 1) == 1){
			if(!in_array(array($x,$y - 1),$found)){
				findall($im, $x, $y - 1);
			}
		}
	}
}

?>