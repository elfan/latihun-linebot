<?php

include_once('Question.class.php');

class QuestionLineEquation extends Question {
	public function __construct($context = [], $config = []) {
		parent::__construct($context, $config);
  }

  public static function info() {
    return [
      'topic' => 'Persamaan Garis',
      'tags' => [
        'id' => ['persamaan', 'garis', 'gradien', 'fungsi', 'titik']   //one word each
      ]
    ];
  }

	protected function getRandomParameters($level = 0) {
    //m = (y2 - y1) / (x2 - x1)
    //y - y1 = m(x - x1)
    //(x2 - x1)(y - y1) = (y2 - y1)(x - x1)
    //ax + by = c
    //a = dy
    //b = -dx
    //c = (dy * x1) - (dx * y1)
    if ($level == 0) { //easy
      $xmin = -3;
      $xmax = 3;
      $ymin = -3;
      $ymax = 3;
    }
    else if ($level == 1) {  //medium
      $xmin = -8;
      $xmax = 8;
      $ymin = -8;
      $ymax = 8;
    }
    else {  //hard
      $xmin = -12;
      $xmax = 12;
      $ymin = -12;
      $ymax = 12;
    }

    $x1 = rand($xmin, $xmax);
    do {
      $x2 = rand($xmin, $xmax);
      $dx = $x2 - $x1;
    } while ($dx == 0);
    $y1 = rand($ymin, $ymax);
    do {
      $y2 = rand($ymin, $ymax);
      $dy = $y2 - $y1;
    } while ($dy == 0);
    $m = ($dy/$dx < 0 ? '-' : '').(abs($dy) % abs($dx) == 0 ? abs($dy) / abs($dx) : (abs($dx) == 1 ? abs($dy) : abs($dy)."/".abs($dx)));
    $a = $dy;
    $b = -$dx;
    $c = ($dy * $x1) - ($dx * $y1);


		$params = [
      'x1' => $x1,
      'x2' => $x2,
      'y1' => $y1,
      'y2' => $y2,
      'm' => $m,
			'a' => $a,
      'b' => $b,
      'c' => $c,
      'sum' => $a + $b + $c,
			'lang' => 'id',
		];

    $hint = "Rumus persamaan garis adalah y-y1 = m(x-x1) dimana gradien m = (y2-y1) / (x2-x1)";

    $sentences = [
    ];

    if ($level == 0) {  //easy
      $sentences = array_merge($sentences, [
        ["Gradien persamaan garis yang melalui titik ({x1}, {y1}) dan ({x2}, {y2}) adalah ...", "m"],
      ]);
    }
    else if ($level > 0) {  //medium or hard
      $sentences = array_merge($sentences, [
        ["Sebuah garis dengan gradien {m} melalui titik ({x1}, {y1}) dan ({x2}, a). Berapakah a?", "y2"],
      ]);
    }

    if ($level == 2) {  //hard
      $sentences = array_merge($sentences, [
        ["Persamaan garis yang melalui titik ({x1}, {y1}) dan ({x2}, {y2}) adalah ax + by = c. Berapakah a + b + c?", "sum"],
      ]);
    }


		$sentence = $sentences[ rand(0, sizeof($sentences) - 1) ];
		$result = $this->createSentence($sentence, $params);
		$params['sentence'] = $result['sentence'];
		$params['unknown'] = $result['unknown'];
		$params['hint'] = $hint;

		//error_log(var_export($params, true));

		return $params;

	}
}
