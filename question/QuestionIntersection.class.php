<?php

include_once('Question.class.php');

class QuestionIntersection extends Question {
	public function __construct($context = [], $config = []) {
		parent::__construct($context, $config);
  }

  public static function info() {
    return [
      'topic' => 'Irisan Himpunan',
      'tags' => [
        'id' => ['irisan', 'himpunan', 'venn', 'siswa', 'senang']   //one word each
      ]
    ];
  }

	protected function getRandomParameters($level = 0) {
    //S = A + B - (A&B) + !(A|B)
    if ($level == 0) { //easy
      $a = rand(4, 9);
      $b = rand(4, 9);
      $anb = rand(2, min($a, $b) - 1);
      $iaob = rand(2, 9);
      $s = $a + $b - $anb + $iaob;
    }
    else if ($level == 1) {  //medium
      $a = rand(10, 50);
      $b = rand(10, 50);
      $anb = rand(5, min($a, $b) - 3);
      $iaob = rand(10, 50);
      $s = $a + $b - $anb + $iaob;
    }
    else {  //hard
      $a = rand(40, 100);
      $b = rand(40, 100);
      $anb = rand(10, min($a, $b) - 10);
      $iaob = rand(30, 100);
      $s = $a + $b - $anb + $iaob;
    }

    $subjects = [
      ["fisika", "matematika", "biologi"],
      ["Bahasa Indonesia", "Bahasa Inggris"],
      ["basket", "futsal", "badminton", "atletik"],
      ["menyanyi", "menari", "main musik"],
      ["memanjat tebing", "arung jeram"],
      ["makan baso", "makan nasi goreng", "makan sate", "makan bubur ayam", "makan gado-gado"],
    ];
    $items = $subjects[ array_rand($subjects) ];
    list($i1, $i2) = array_rand($items, 2);
    $subject1 = $items[$i1];
    $subject2 = $items[$i2];

		$params = [
			'a' => $a,
      'b' => $b,
      'anb' => $anb,
      'ax' => $a - $anb,  //only $a
      'bx' => $b - $anb,  //only $b
      'diff' => abs($a - $b),
      'single' => $a - $anb + $b - $anb,  //ax + bx
      'iaob' => $iaob,
      's' => $s,
      'subject1' => $subject1,
      'subject2' => $subject2,
			'lang' => 'id',
		];

    $hint = "Rumusnya: jumlahTotal = senangA + senangB - senangAjugaB + tidakSenangAB\natau jumlahTotal = hanyaSenangA + hanyaSenangB + senangAjugaB + tidakSenangAB";

    $sentences = [
      ["Dari total {s} siswa, {a} siswa senang {subject1}, {b} siswa senang {subject2}, dan {anb} senang keduanya. Banyak siswa yang tidak senang {subject1} maupun {subject2} adalah ...", "iaob"],
      ["Ada {s} siswa dimana {a} siswa senang {subject1}, {b} siswa senang {subject2}, dan {anb} senang keduanya. Banyak siswa yang tidak senang keduanya adalah ...", "iaob"],
    ];

    if ($level == 0) {  //easy
      $sentences = array_merge($sentences, [
        ["{a} siswa senang {subject1}, dan sebagian di antaranya juga senang {subject2}. Siswa yang hanya senang {subject2} saja ada {bx}, dan siswa yang tidak senang keduanya ada {iaob}. Ada berapa jumlah siswa keseluruhan?", "s"],
        ["{anb} siswa senang {subject1} dan juga {subject2}, sedangkan {iaob} siswa tidak senang keduanya. Siswa yang hanya senang {subject1} saja ada {ax}, dan siswa yang senang {subject2} saja ada {bx} orang. Ada berapa jumlah siswa keseluruhan?", "s"],
      ]);
    }
    else if ($level > 0) {  //medium or hard
      $sentences = array_merge($sentences, [
      ]);
    }

    if ($level == 2) {  //hard
      $sentences = array_merge($sentences, [
        ["Dari total {s} siswa, {anb} siswa senang {subject1} dan juga {subject2}, sedangkan {iaob} siswa tidak senang keduanya. Berapa jumlah siswa yang hanya menyenangi salah satu saja?", "single"],
        ["Dari total {s} siswa, {ax} siswa hanya senang {subject1} saja, {bx} siswa hanya senang {subject2} saja, dan {iaob} siswa tidak senang keduanya. Berapa jumlah siswa yang menyenangi keduanya?", "anb"],
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
