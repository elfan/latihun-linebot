<?php

include_once('Question.class.php');

class QuestionSimpleAlgebra extends Question {
	public function __construct($context = [], $config = []) {
		parent::__construct($context, $config);
  }

  public static function info() {
    return [
      'topic' => 'Aljabar',
      'tags' => [
        'id' => ['aljabar', 'x', 'persamaan']
      ]
    ];
  }

	protected function getRandomParameters($level = 0) {
		//$a * ($x + $b) + $c = $d * ($x + $e) + $f
    //$a$x + $a$b + $c = $dx + $de + $f
    //$a$x - $d$x = $d$e + $f - $a$b + $c
    //$a$x - $d$x + $a$b + $c - $d$e = $f
    //$x = ($d$e + $f - $a$b - $c) / ($a - $d)
    if ($level == 0) { //easy
      //$b = 0, $c = 0, $f = 0, $g = 0
      //($a$x - $d$x + $a$b) / $d = $e
      $a = rand(2, 5);
      do {
        $d = rand(2, 5);
      } while ($d == $a);
      $b = 0;
      $c = 0;
      $x = rand(1, 5) * $d * (rand(0,1)==0 ? 1 : -1);
      $f = 0;
      $e = (($a * $x) - ($d * $x) + ($a * $b)) / $d;
      $g = 0;
      $y = $x + $g;
    }
    else if ($level == 1) {  //medium
      //$c = 0, $e may 0
      //$a$x - $d$x + $a$b + $c - $d$e = $f
      $a = rand(2, 7);
      $b = rand(1, 7) * (rand(0,1)==0 ? 1 : -1);
      $c = 0;
      $x = rand(1, 10) * (rand(0,1)==0 ? 1 : -1);
      do {
        $d = rand(2, 10) * (rand(0,1)==0 ? 1 : -1);
      } while ($d == $a);
      do {
        $e = rand(0, 7) * (rand(0,1)==0 ? 1 : -1);
      } while ($e == $b);
      $f = ($a * $x) - ($d * $x) + ($a * $b) + $c - ($d * $e);
      $g = rand(1, 5) * (rand(0,1)==0 ? 1 : -1);
      $y = $x + $g;
    }
    else {  //hard
      //$a$x - $d$x + $a$b + $c - $d$e = $f
      $a = rand(2, 10) * (rand(0,1)==0 ? 1 : -1);
      $b = rand(1, 10) * (rand(0,1)==0 ? 1 : -1);
      $c = rand(0, 10) * (rand(0,1)==0 ? 1 : -1);
      $x = rand(1, 10) * (rand(0,1)==0 ? 1 : -1);
      do {
        $d = rand(2, 10) * (rand(0,1)==0 ? 1 : -1);
      } while ($d == $a);
      do {
        $e = rand(1, 10) * (rand(0,1)==0 ? 1 : -1);
      } while ($e == $b);
      $f = ($a * $x) - ($d * $x) + ($a * $b) + $c - ($d * $e);
      $g = rand(1, 10) * (rand(0,1)==0 ? 1 : -1);
      $y = $x + $g;
    }

		$params = [
			'a' => $a,
      'b' => $b,
      'c' => $c,
      'd' => $d,
			'e' => $e,
			'f' => $f,
			'x' => $x,
			'g' => $g,
      'y' => $y,
			'lang' => 'id',
		];

    $hint = "Contoh yah, misalnya 4(x+5) = 2x, bisa diuraikan jadi 4x+20 = 2x.\nLalu pindahkan x ke sisi kiri dan angka biasa ke sisi kanan, menjadi 4x-2x = -20, selanjutnya 2x = -20, jadi x = -10";

    if ($level == 0) {  //easy
      $sentences = [
        ["Diketahui {d}(x{+e}) = {a}x. Nilai dari x adalah ...", "y"],
      ];
    }
    else {  //medium or hard
      if ($e == 0) {
        $sentences = [
          ["Diketahui {a}(x{+b}){+c} = {d}x{+f}. Nilai dari x{+g} adalah ...", "y"],
        ];
      }
      else {
        $sentences = [
          ["Diketahui {a}(x{+b}){+c} = {d}(x{+e}){+f}. Nilai dari x{+g} adalah ...", "y"],
        ];
      }
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
