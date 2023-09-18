<?php
if (!isset($_GET['iid'])) {
  exit;
}

require_once('tools.php');

$iid = intval($_GET['iid'], 16);
$seed = substr($iid, 0, strlen($iid) - 2);
$level = substr($iid, strlen($iid) - 2, 1);
$lang = (int)substr($iid, strlen($iid) - 1, 1);
if ($lang < 0 || $lang > 1) return;

$module = "QuestionCircleGeometry";
include_once('question/' . $module . '.class.php');
$context = [
  "lang" => ["id", "en"][(int)$lang],
  "level" => $level,
  "seed" => $seed,
];
$q = new $module($context);

draw_circle($q->params);



function draw_circle($param) {
  $outwidth = 600;
  $outheight = 600;

  $resampled = true;


  $imgwidth = ($resampled ? 2 * $outwidth : $outwidth);
  $imgheight = ($resampled ? 2 * $outheight : $outheight);
  $padding = $imgwidth * 0.1; //10% padding

  $img = imagecreatetruecolor($imgwidth, $imgheight);
  $white = imagecolorallocate($img, 255, 255, 255);
  $lightgray = imagecolorallocate($img, 240, 244, 244);
  $darkgray = imagecolorallocate($img, 117, 115, 114);

  $bluish = imagecolorallocate($img, 190, 199, 219);
  $redish = imagecolorallocate($img, 219, 190, 199);
  $greenish = imagecolorallocate($img, 190, 219, 199);
  $yellowish = imagecolorallocate($img, 226, 219, 190);
  $grayish = imagecolorallocate($img, 199, 190, 191);

  $shadecolors = [
    'blue' => $bluish,
    'red' => $redish,
    'green' => $greenish,
    'yellow' => $yellowish,
    'gray' => $grayish,
  ];

  $bgcolor = $white;
  $linecolor = $darkgray;
  if (!isset($param['color'])) $param['color'] = 'blue';
  $shadecolor = $shadecolors[ $param['color'] ];

  $xl = $padding;   //x left
  $yt = $padding;   //y top
  $width = $imgwidth - (2 * $padding);
  if ($width % 2 == 0) $width++;  //make it an odd to have a perfect center
  $height = $imgheight - (2 * $padding);
  if ($height % 2 == 0) $height++;  //make it an odd to have a perfect center
  $xr = $xl + $width - 1;  //x right
  $yb = $yt + $height - 1; //y bottom
  $xc = $xl + floor($width / 2);
  $yc = $yt + floor($height / 2);
  $r = ceil($width/2);
  $q = ceil($r / 2);  //half r
  $xlc = $xl + $q;  //between xl and xc
  $xcr = $xc + $q;  //between xc and xr
  $ytc = $yt + $q;  //between yt and yc
  $ycb = $yc + $q;  //between yc and yb

  //background
  imagefilledrectangle($img, 0, 0, $imgwidth-1, $imgheight-1, $bgcolor);

  //line thickness
  $thin = 1;
  $thick = 10;
  imagesetthickness($img, $thin);


  $feats = [
    "thin",
    "border",
    "thick",

    "hl_lc",
    "hl_cr",
    "vl_tc",
    "vl_cb",


    "bdl_d",
    "bdl_u",

    "sdl_u_tl",
    "sdl_d_tl",
    "sdl_d_bl",
    "sdl_u_br",


    "b_fc",

    "sqa_ctl",
    "sqa_ctr",
    "sqa_cbr",
    "sqa_cbl",


    "b_hc_l",
    "b_hc_r",
    "b_hc_t",
    "b_hc_b",


    "sfc_c",
    "sfc_l",
    "sfc_r",
    "sfc_t",
    "sfc_b",
    "sfc_tl",
    "sfc_tr",
    "sfc_br",
    "sfc_bl",


    "sqa_tl",
    "sqa_tr",
    "sqa_br",
    "sqa_bl",


    "bqa_tl",
    "bqa_tr",
    "bqa_br",
    "bqa_bl",


    "hba_t",
    "hba_r",
    "hba_b",
    "hba_b",

  ];

  $feats = $param['feats'];
  if (empty($feats)) $feats = [];

  foreach ($feats as $feat) {
    switch ($feat) {
      case "thin":
        //thin line
        imagesetthickness($img, $thin);
        break;

      case "thick":
        //thick line
        imagesetthickness($img, $thick);
        break;

      case "border":
        //main border line
        imagerectangle($img, $xl, $yt, $xr, $yb, $darkgray);  //border
        break;

      case "vl_tc":
        //vertical line from top to the center
        imageline($img, $xc, $yt, $xc, $yc, $darkgray); //vertical top center
        break;

      case "vl_cb":
        //vertical line from center to the bottom
        imageline($img, $xc, $yc, $xc, $yb, $darkgray); //vertical center bottom
        break;

      case "hl_lc":
        //horizontal line from left to the center
        imageline($img, $xl, $yc, $xc, $yc, $darkgray); //horizontal middle
        break;

      case "hl_cr":
        //horizontal line from center to the right
        imageline($img, $xc, $yc, $xr, $yc, $darkgray); //horizontal middle
        break;

      case "bdl_d":
        //big diagonal line downward
        imageline($img, $xl, $yt, $xr, $yb, $darkgray); //diagonal top left to bottom right
        break;

      case "bdl_u":
        //big diagonal line upward
        imageline($img, $xl, $yb, $xr, $yt, $darkgray); //diagonal bottom left to topright
        break;

      case "sdl_u_tl":
        //small diagonal line upward on the top left
        imageline($img, $xl, $yc, $xc, $yt, $darkgray); //small diagonal on the top left
        break;

      case "sdl_d_tl":
        //small diagonal line upward on the top right
        imageline($img, $xc, $yt, $xr, $yc, $darkgray); //small diagonal on the top right
        break;

      case "sdl_d_bl":
        //small diagonal line upward on the top left
        imageline($img, $xl, $yc, $xc, $yb, $darkgray); //small diagonal on the bottom left
        break;

      case "sdl_u_br":
        //small diagonal line upward on the bottom right
        imageline($img, $xc, $yb, $xr, $yc, $darkgray); //small diagonal on the bottom right
        break;

      case "b_fc":
        //big full circle
        imagearc($img, $xc, $yc, $width, $height, 0, 360, $linecolor);
        break;

      case "b_hc_l":
        //big half circle from the left
        imagearc($img, $xlc, $yc, $width, $height, 270, 90, $linecolor);
        break;

      case "b_hc_r":
        //big half circle from the right
        imagearc($img, $xcr, $yc, $width, $height, 90, 270, $linecolor);
        break;

      case "b_hc_t":
        //big half circle from the top
        imagearc($img, $xc, $ytc, $width, $height, 0, 180, $linecolor);
        break;

      case "b_hc_b":
        //big half circle from the bottom
        imagearc($img, $xc, $ycb, $width, $height, 180, 0, $linecolor);
        break;

      case "sfc_c":
        //a small full circle on the center
        imagearc($img, $xc, $yc, $r, $r, 0, 360, $linecolor);
        break;

      case "sfc_l":
        //a small full circle on the left
        imagearc($img, $xlc, $yc, $r, $r, 0, 360, $linecolor);
        break;

      case "sfc_r":
        //a small full circle on the right
        imagearc($img, $xcr, $yc, $r, $r, 0, 360, $linecolor);
        break;

      case "sfc_t":
        //a small full circle on the top
        imagearc($img, $xc, $ytc, $r, $r, 0, 360, $linecolor);
        break;

      case "sfc_b":
        //a small full circle on the bottom
        imagearc($img, $xc, $ycb, $r, $r, 0, 360, $linecolor);
        break;

      case "sfc_tl":
        //a small full circle on the top left
        imagearc($img, $xlc, $ytc, $r, $r, 0, 360, $linecolor);
        break;

      case "sfc_tr":
        //a small full circle on the top right
        imagearc($img, $xcr, $ytc, $r, $r, 0, 360, $linecolor);
        break;

      case "sfc_br":
        //a small full circle on the bottom right
        imagearc($img, $xcr, $ycb, $r, $r, 0, 360, $linecolor);
        break;

      case "sfc_bl":
        //a small full circle on the bottom left
        imagearc($img, $xlc, $ycb, $r, $r, 0, 360, $linecolor);
        break;

      case "sqa_ctl":
        //a small quarter arc from center to the top left
        imagearc($img, $xc, $yc, $width, $height, 180, 270, $linecolor);
        break;

      case "sqa_ctr":
        //a small quarter arc from center to the top right
        imagearc($img, $xc, $yc, $width, $height, 270, 0, $linecolor);
        break;

      case "sqa_cbr":
        //a small quarter arc from center to the bottom right
        imagearc($img, $xc, $yc, $width, $height, 0, 90, $linecolor);
        break;

      case "sqa_cbl":
        //a small quarter arc from center to the bottom left
        imagearc($img, $xc, $yc, $width, $height, 90, 180, $linecolor);
        break;

      case "sqa_tl":
        //a small quarter arc from the top left
        imagearc($img, $xl, $yt, $width, $height, 0, 90, $linecolor);
        break;

      case "sqa_tr":
        //a small quarter arc from the top right
        imagearc($img, $xr, $yt, $width, $height, 90, 180, $linecolor);
        break;

      case "sqa_br":
        //a small quarter arc from the bottom right
        imagearc($img, $xr, $yb, $width, $height, 180, 270, $linecolor);
        break;

      case "sqa_bl":
        //a small quarter arc from the bottom left
        imagearc($img, $xl, $yb, $width, $height, 270, 360, $linecolor);
        break;


      case "sqa_lc_t":
        //a small quarter arc from the left center to the top
        imagearc($img, $xl, $yc, $width, $height, 270, 00, $linecolor);
        break;

      case "sqa_lc_b":
        //a small quarter arc from the left center to the bottom
        imagearc($img, $xl, $yc, $width, $height, 0, 90, $linecolor);
        break;

      case "sqa_ct_l":
        //a small quarter arc from the center top to the left
        imagearc($img, $xc, $yt, $width, $height, 90, 180, $linecolor);
        break;

      case "sqa_ct_r":
        //a small quarter arc from the center top to the right
        imagearc($img, $xc, $yt, $width, $height, 0, 90, $linecolor);
        break;

      case "sqa_rc_t":
        //a small quarter arc from the right center to the top
        imagearc($img, $xr, $yc, $width, $height, 180, 270, $linecolor);
        break;

      case "sqa_rc_b":
        //a small quarter arc from the right center to the bottom
        imagearc($img, $xr, $yc, $width, $height, 90, 180, $linecolor);
        break;

      case "sqa_cb_r":
        //a small quarter arc from the center bottom to the right
        imagearc($img, $xc, $yb, $width, $height, 270, 0, $linecolor);
        break;

      case "sqa_cb_l":
        //a small quarter arc from the center bottom to the left
        imagearc($img, $xc, $yb, $width, $height, 180, 270, $linecolor);
        break;



      case "bqa_tl":
        //a big quarter arc from the top left
        imagearc($img, $xl, $yt, $width * 2, $height * 2, 0, 90, $linecolor);
        break;

      case "bqa_tr":
        //a big quarter arc from the top right
        imagearc($img, $xr, $yt, $width * 2, $height * 2, 90, 180, $linecolor);
        break;

      case "bqa_br":
        //a big quarter arc from the bottom right
        imagearc($img, $xr, $yb, $width * 2, $height * 2, 180, 270, $linecolor);
        break;

      case "bqa_bl":
        //a big quarter arc from the bottom left
        imagearc($img, $xl, $yb, $width * 2, $height * 2, 270, 360, $linecolor);
        break;

      case "hba_t":
        //a half big arc from the top
        imagearc($img, $xc, $yt, $width, $height, 0, 180, $linecolor);
        break;

      case "hba_r":
        //a half big arc from the right
        imagearc($img, $xr, $yc, $width, $height, 90, 270, $linecolor);
        break;

      case "hba_b":
        //a half big arc from the bottom
        imagearc($img, $xc, $yb, $width, $height, 180, 360, $linecolor);
        break;

      case "hba_b":
        //a half big arc from the bottom
        imagearc($img, $xl, $yc, $width, $height, 270, 90, $linecolor);
        break;

      default:
        if (preg_match("/^(x\w{1,2})([+-]),(y\w{1,2})([+-])$/", $feat, $match)) { //e.g. "xl+,yc-"
          $dist = 10;
          $fx = ${$match[1]} + ($match[2] == '+' ? 1 : -1) * $dist;
          $fy = ${$match[3]} + ($match[4] == '+' ? 1 : -1) * $dist;
          imagefill($img, $fx, $fy, $shadecolor);
        }
        else if (preg_match("/^(x\w{1,2}),(y\w{1,2}),([1-4]),(\d{1,3}),(\d{1,3})$/", $feat, $match)) { //e.g. "xl,yc,2,270,90"
          $ax = ${$match[1]};
          $ay = ${$match[2]};
          $r = (int)$match[3] * $width / 2;
          $from = (int)$match[4];
          $to = (int)$match[5];
          imagearc($img, $ax, $ay, $r, $r, $from, $to, $linecolor);
        }
        else if (preg_match("/^(x\w{1,2}),(y\w{1,2}),(x\w{1,2}),(y\w{1,2})$/", $feat, $match)) { //e.g. "xl,yc,xr,yc"
          imageline($img, ${$match[1]}, ${$match[2]}, ${$match[3]}, ${$match[4]}, $darkgray); //small diagonal on the bottom right
        }
    }
  }

  $degree = mt_rand(0, 3) * 90;
  $img = imagerotate($img, $degree, $bgcolor);


  $uppertext = $param['diameter'] . " cm";
  $size = 50;
  $font = './arial.ttf';
  $box = imagettfbbox ($size, 0, $font, $uppertext);
  $tw = $box[2] - $box[0];
  $th = $box[5] - $box[1];
  imagettftext($img, $size, 0, $xc - ($tw / 2), $yt - ($padding / 2) - ($th / 2), $darkgray, $font, $uppertext);


  //create the output image
  if ($resampled) {
    $outimg = imagecreatetruecolor($outwidth, $outheight);
    imagecopyresampled($outimg, $img, 0, 0, 0, 0, $outwidth, $outheight, $imgwidth, $imgheight);
  }

  //output the image
  header("Content-type: image/png");
  imagepng($resampled ? $outimg : $img);
  imagedestroy($img);
  imagedestroy($outimg);
}
