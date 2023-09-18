<?php

include_once('Question.class.php');

class QuestionWholeNumberOperation extends Question {
	public function __construct($context = [], $config = []) {
		parent::__construct($context, $config);
	}

  public static function info() {
    return [
      'topic' => 'Operasi Bilangan Bulat',
      'tags' => [
        'id' => ['bilangan', 'bulat']
      ]
    ];
  }

	protected function getRandomParameters($level = 0) {
    //   × : + -
		//$ui = $a + (($i - 1) * $b)
		if ($level == 0) {	//easy
      
			$a = rand(1, 3);
			$b = rand(2, 3);
			$nmin = 1;
			$nmax = 2;
			$first = rand(2, 3) * 2;
		}
		else if ($level == 1) {
			$a = rand(2, 8);
			$b = rand(2, 5);
			$nmin = 2;
			$nmax = 5;
			$first = rand(5, 10) * 2;
		}
		else {
			$a = rand(5, 15);
			$b = rand(3, 7);
			$nmin = 3;
			$nmax = 7;
			$first = rand(30, 50);
		}

		$i = [];
		for ($n = 1; $n <= 5; $n++) {
			$i[$n] = rand($nmin, $nmax) + ($n > 1 ? $i[$n - 1] : 0);
		}
		if (rand(0,3) == 0) {	//25% probability to swap i2 and i3
			$temp = $i[3];
			$i[3] = $i[2];
			$i[2] = $temp;
		}

		$params = [
			'a' => $a,
			'b' => $b,
			'first' => $first,
			'lang' => 'id',
		];

		$u = [];
		for ($n = 1; $n <= 5; $n++) {
			$u[$n] = $a + (($i[$n] - 1) * $b);
			$params['i' . $n] = $i[$n];
			$params['u' . $n] = $u[$n];
		}

		$sumfirst = 0;
		for ($n = 1; $n <= $first; $n++) {
			$sumfirst += $a + (($n - 1) * $b);
		}

		$params['sum12'] = $u[1] + $u[2];
		$params['sum34'] = $u[3] + $u[4];
		$params['sum123'] = $u[1] + $u[2] + $u[3];
		$params['sumfirst'] = $sumfirst;
    $params['sumsquare'] = ($u[1] * $u[1]) + ($u[2] * $u[2]) + ($u[3] * $u[3]);

    $hint = "Rumus suku ke-n adalah Un = U1 + (n-1)b, dimana b adalah besarnya selisih antara dua suku berurutan, dan selisihnya selalu sama. Un itu artinya suku ke-n, U1 itu suku ke-1. Contoh, jika suku ke-2 adalah 5 dan suku ke-4 adalah 11, artinya selisihnya adalah (11 - 5) / (4 - 2) = 3";

		$sentences = [
			["Suatu barisan aritmatika diketahui suku ke-{i1} adalah {u1} dan suku ke-{i2} adalah {u2}, maka suku ke-{i3} adalah ...", "u3"],
			["Suku ke-{i1} dan suku ke-{i2} suatu barisan aritmatika berturut-turut adalah {u1} dan {u2}. Suku ke-{i3} barisan tersebut adalah ...", "u3"],
			["Jika suku ke-{i1} adalah {u1} dan suku ke-{i2} adalah {u2}, maka suku-{i3} dalam barisan aritmatika tersebut adalah ...", "u3"],
		];

		if ($level >= 1) {
			$sentences = array_merge($sentences, [
				["Diketahui barisan aritmatika dengan U{i1} + U{i2} + U{i3} = {sum123} dan U{i4} = {u4}. Suku ke-{i5} barisan tersebut adalah ...", "u5"],
				["Suatu barisan aritmatika diketahui suku ke-{i1} adalah {u1} dan suku ke-{i4} adalah {u4}, maka U{i1} + U{i2} + U{i3} adalah ...", "sum123"],
				["Diketahui barisan aritmatika dengan U{i1} + U{i2} = {sum12} dan U{i3} + U{i4} = {sum34}. Suku ke-{i5} barisan tersebut adalah ...", "u5"],
				["Diketahui suku ke-{i1} adalah {u1} dan suku ke-{i4} adalah {u4}. Jumlah {first} suku pertama barisan bilangan aritmatika tersebut adalah ...", "sumfirst"],
        ["Diketahui suku pertama suatu deret aritmetika adalah {u1} dan suku ke-{i5} adalah {u5}. Jumlah 3 suku pertama deret tersebut adalah ...", "sum123"],
        ["Jumlah 3 buah suku pertama sebuah barisan aritmetika adalah {sum123} dan jumlah kuadratnya adalah {sumsquare}. Suku ke-{i1} barisan tersebut adalah ...", "u1"],
			]);

			if ((($i[1] + $i[2] + $i[3] - 3) % 3 == 0) && (($u[1] + $u[2] + $u[3]) % 3 == 0)) {	//index and sum are divisible by 3
				$i[4] = (($i[1] + $i[2] + $i[3] - 3) / 3) + 1;
				$u[4] = $a + (($i[4] - 1) * $b);
				$params['i4'] = $i[4];
				$params['u4'] = $u[4];

				$sentences[] = ["Jika U{i1} + U{i2} + U{i3} = {sum123}, maka suku ke-{i4} barisan aritmatika tersebut adalah ...", "u4"];
			}

			if ($a >= 8 && $b <= 5) {
				$sentences[] = ["Dalam sebuah aula terdapat {a} kursi pada baris pertama dan setiap baris berikutnya bertambah {b} kursi dari baris di depannya. Jika aula tersebut memuat {first} baris kursi, maka banyaknya kursi di aula tersebut adalah ...", "sumfirst"];
			}
		}

		$sentence = $sentences[ rand(0, sizeof($sentences) - 1) ];
		$result = $this->createSentence($sentence, $params);
		$params['sentence'] = $result['sentence'];
		$params['unknown'] = $result['unknown'];
		$params['hint'] = $hint;

		return $params;
	}
}
