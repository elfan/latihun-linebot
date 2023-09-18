<?php

include_once('Question.class.php');

class QuestionWorkerDay extends Question {
	public function __construct($context = [], $config = []) {
		parent::__construct($context, $config);
		/*
		$this->template = '[{
		  "type": "text",
		  "text": "Suatu pekerjaan dapat diselesaikan oleh 15 orang pekerja dalam waktu 12 minggu. Jika pekerjaan itu harus selesai dalam waktu 9 minggu, maka banyak pekerja yang harus ditambah adalah..."
		},
		{
		  "type": "template",
		  "altText": "this is a buttons template",
		  "template": {
			  "type": "buttons",
			  "thumbnailImageUrl1": "https://elfan.net/line/bot' . rand(1,3) . '.jpg",
			  "title1": "Tanya",
			  "text": "Pilih:",
			  "actions": [
				  {
					"type": "postback",
					"label": "3 orang",
					"data": "p1=15&w1=12&w2=9&p1=3",
					"text": "3"
				  },
				  {
					"type": "postback",
					"label": "4 orang",
					"data": "p1=15&w1=12&w2=9&p1=4",
					"text": "4"
				  },
				  {
					"type": "postback",
					"label": "5 orang",
					"data": "p1=15&w1=12&w2=9&p1=5",
					"text": "5"
				  },
				  {
					"type": "postback",
					"label": "20 orang",
					"data": "p1=15&w1=12&w2=9&p1=20",
					"text": "20"
				  }
			  ]
		  }
		}]';
		*/
  }

  public static function info() {
    return [
      'topic' => 'Rasio',
      'tags' => [
        'id' => ['pekerja', 'pekerjaan', 'rasio', 'minggu']
      ]
    ];
  }

  public function test() {
		$str = [];
		for ($i = 0; $i < 10; $i++) {
			$str[] = implode(", ", $this->personWork([
				'minNumber' => 3,
				'maxTotal' => 200,
				'ratioType' => 'hard',
				'uniqueSide' => true,
			]));
		}
		return implode("\n", $str);
	}

	public function personWork($par = []) {
		//default parameters
		$par = array_merge([
			'minNumber' => 1,
			'maxTotal' => 200,
			'ratioType' => '',	//'simple','hard', '' (both)
			'uniqueSide' => false,
		], $par);
		$minTotal = $par['minNumber'] * $par['minNumber'];
		if ($minTotal < 2) $minTotal = 2;	//so that at least there are 2 factor numbers

		if ($par['minNumber'] > 1 || $par['uniqueSide'] || $par['ratioType'] != '') {
			//get the factors
			$numbers = [];
			for ($i = $minTotal; $i <= $par['maxTotal']; $i++) {
				$numbers[] = $i;
			}
			if (sizeof($numbers) == 0) {
				return [];	//impossible
			}

			$minFactors = 2;
			if ($par['uniqueSide']) $minFactors++;

			do {
				do {
					$idx = rand(0, sizeof($numbers) - 1);
					$total = $numbers[ $idx ];
					$factors = $this->getFactors($total, $par['minNumber']);
					$tryAgain = (sizeof($factors) < $minFactors);	//not enough factors
					if ($tryAgain) {
						array_splice($numbers, $idx, 1);	//remove from the possible numbers
						if (sizeof($numbers) == 0) {
							return [];	//impossible
						}
					}
				} while ($tryAgain);	//try another number

				//get the first pair
				$p1 = any($factors);
				$w1 = $total / $p1;

				//get the remaining factors
				$exclude = [$p1];
				if ($par['uniqueSide']) $exclude[] = $w1;
				$factors = $this->exclude($factors, $exclude);	//remove the already selected numbers

				//get the second pair
				$bigTryAgain = false;
				if ($par['ratioType'] != '') {
					do {
						$idx = rand(0, sizeof($factors) - 1);
						$p2 = $factors[ $idx ];
						if ($par['ratioType'] == 'simple') {
							$tryAgain = ($p1 % $p2 != 0 && $p2 % $p1 != 0);
						}
						else {	//hard
							$tryAgain = ($p1 % $p2 == 0 || $p2 % $p1 == 0);
						}

						if ($tryAgain) {
							array_splice($factors, $idx, 1);	//remove from the possible numbers
							if (sizeof($factors) == 0) {
								$bigTryAgain = true;	//cannot find the second pair, try another number
								break;
							}
						}
					} while ($tryAgain);	//try another $p2
					$w2 = $total / $p2;
				}
				else {
					$p2 = any( $factors );
					$w2 = $total / $p2;
				}

			} while ($bigTryAgain);	//try another number



		}
		else {	//simple
			//get the factors
			$total = rand($minTotal, $par['maxTotal']);
			$factors = $this->getFactors($total);

			//get the first pair
			$p1 = any($factors);
			$w1 = $total / $p1;

			//get the remaining factors
			$exclude = [$p1];
			$factors = $this->exclude($factors, $exclude);	//remove the already selected numbers

			//get the second pair
			$p2 = any( $factors );
			$w2 = $total / $p2;
		}

		return [$p1, $w1, $p2, $w2];
	}

	protected function getRandomParameters($level = 0) {
		//$p1 * $w1 = $p2 * $w2
		if ($level == 0) {	//easy
			list($p1, $w1, $p2, $w2) = $this->personWork([
				'minNumber' => 1,
				'maxTotal' => 4,
				'ratioType' => 'simple',
				'uniqueSide' => false,
			]);
			/*
				small numbers multiplied <= 4, no fractions in ratio
				[1, 2, 2, 1],
				[1, 3, 3, 1],
				[1, 4, 2, 2],
				[1, 4, 4, 1],
				[2, 1, 1, 2],
				[3, 1, 1, 3],
				[2, 2, 1, 4],
				[2, 2, 4, 1],
				[4, 1, 1, 4],
			*/
		}
		else if ($level == 1) {	//medium
			list($p1, $w1, $p2, $w2) = $this->personWork([
				'minNumber' => 2,
				'maxTotal' => 30,
				'ratioType' => 'simple',
				'uniqueSide' => true,
			]);
			/*
				larger numbers > 1 multiplied <= 30, no fractions in ratio, no same number left right
				[2, 6, 4, 3],
				[2, 8, 4, 4],
				[2, 9, 6, 3],
				[2, 14, 4, 7],
				[2, 15, 6, 5],
				[2, 15, 10, 3],
				[3, 4, 6, 2],
				[3, 6, 9, 2],
				...
			*/
		}
		else {	//hard
			list($p1, $w1, $p2, $w2) = $this->personWork([
				'minNumber' => 3,
				'maxTotal' => 200,
				'ratioType' => 'hard',
				'uniqueSide' => true,
			]);
			/*
				larger numbers > 2 multiplied <= 200 with fractions in ratio, no any same number
				[3, 4, 2, 6],
				[3, 6, 2, 9],
				[3, 8, 4, 6],
				[3, 8, 2, 12],
				[3, 10, 5, 6],
				[3, 10, 2, 15],
				[4, 3, 6, 2],
				[4, 5, 10, 2],
				...
			*/
		}

		$dp = $p2 - $p1;
		$dw = $w2 - $w1;

    $hint = "Rumusnya p1 x w1 = p2 x w2, yaitu banyaknya pekerjaan yg harus diselesaikan tetap sama meskipun jumlah pekerja dan waktunya berbeda";

		$sentences = [];
		if ($dp > 0) {	// p increases, w decreases
			$sentences[] = ["Suatu pekerjaan dapat diselesaikan oleh {p1} orang pekerja dalam waktu {w1} minggu. Jika pekerjaan itu harus selesai dalam waktu {w2} minggu, maka banyaknya pekerja yang harus ditambah adalah...", "dp"];
			$sentences[] = ["Suatu pekerjaan dapat diselesaikan oleh {p1} orang pekerja dalam waktu {w1} minggu. Jika ada tambahan pekerja sebanyak {dp} orang, maka pekerjaan itu akan selesai dalam berapa minggu?", "w2"];
		}
		else {	//p decreases, w increases
			$sentences[] = ["Suatu pekerjaan dapat diselesaikan oleh {p1} orang pekerja dalam waktu {w1} minggu. Jika hanya tersedia {p2} orang saja, maka pekerjaan itu akan membutuhkan waktu selama berapa minggu?", "w2"];
			$sentences[] = ["Suatu pekerjaan dapat diselesaikan oleh {p1} orang pekerja dalam waktu {w1} minggu. Jika hanya tersedia {p2} orang saja, maka pekerjaan itu akan terlambat selama berapa minggu?", "dw"];
		}

		$params = [
      'p1' => $p1,
      'p2' => $p2,
      'w1' => $w1,
      'w2' => $w2,
      'dp' => $dp,
      'dw' => $dw,
      'lang' => 'id'
    ];
		$sentence = $sentences[ rand(0, sizeof($sentences) - 1) ];
		$result = $this->createSentence($sentence, $params);
		$params['sentence'] = $result['sentence'];
		$params['unknown'] = $result['unknown'];
		$params['hint'] = $hint;

		return $params;

	}


}
