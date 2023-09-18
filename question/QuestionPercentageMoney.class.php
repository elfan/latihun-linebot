<?php

include_once('Question.class.php');

class QuestionPercentageMoney extends Question {
	public function __construct($context = [], $config = []) {
		parent::__construct($context, $config);
  }

  public static function info() {
    return [
      'topic' => 'Persen',
      'tags' => [
        'id' => ['persen', 'penjualan', 'jualan', 'keuntungan', 'untung']
      ]
    ];
  }

	protected function getRandomParameters($level = 0) {
		//$base + ($pct/100 * $base) = $price
    //$base = $price / (1 + $pct/100)
    if ($level == 0) { //easy
  		$base = rand(3, 7) * 100; //300rb - 700rb, in 100rb incr
      $pct = [10, 20, 30][rand(0, 2)];
    }
    else if ($level == 1) { //medium
  		$base = rand(3, 7) * 100; //300rb - 700rb, in 100rb incr
      $pct = [5, 10, 15, 20, 25, 30][rand(0, 5)];
    }
    else {  //hard
  		$base = rand(10, 15) * 50; //500rb - 750rb, in 50rb incr
      if ($base % 50 == 0) {
        $pct = [10, 20, 30][rand(0, 2)];
      }
      else {
        $pct = [5, 10, 15, 20, 25, 30][rand(0, 5)];
      }
    }

    $price = $base + ($pct * $base / 100);
    $diff = $price - $base;

		$persons = [
			'generic' => [
				['Rian', 'M'],
				['Ali', 'M'],
				['Abdul', 'M'],
				['Sarah', 'F'],
				['Amanda', 'F',],
				['Budi', 'M'],
				['Jaka', 'M'],
				['Umar', 'M'],
				['Fatin', 'M'],
				['Kiki', 'F'],
				['Hasyim', 'M'],
				['Hana', 'F'],
				['Leo', 'M'],
				['Heri', 'M'],
				['Toni', 'M'],
				['Agung', 'M'],
				['Nur', 'F'],
				['Feny', 'F'],
				['Ami', 'F'],
				['Taufiq', 'M']
			]
		];

		$objects = [
			'cheap' => ['buku', 'pensil', 'kelereng', 'layang-layang', 'es krim', 'perangko'],
			'expensive' => ['sepeda', 'printer', 'televisi', 'tas'],
		];

		$pidx = array_rand($persons['generic']);
		$person = $persons['generic'][$pidx];
		$object = $objects['expensive'][ rand(0, sizeof($objects['expensive']) - 1) ];
		$params = [
			'price' => $price,
      'base' => $base,
      'diff' => $price - $base,
      'pct' => $pct,
			'person' => $person[0],
			'object' => $object,
			'lang' => 'id',
		];

    $hint = "Rumusnya hargaJual = hargaBeli + (hargaBeli x untungPersen / 100)";

    if ($level == 0) { //easy
      $sentences = [
        ["{person} membeli {object} seharga Rp {base} ribu dan ia ingin mendapat untung {pct}%. Berapa harga penjualannya (dalam ribuan)?", "price"],
        ["Berapa harga jual (dalam ribuan) suatu barang yang dibeli dengan harga Rp {base} ribu jika ingin mendapat keuntungan sebesar {pct}% ?", "price"],
      ];
    }
    else if ($level == 1) { //medium
      $sentences = [
        ["{person} membeli {object} seharga Rp {base} ribu dan ia menjualnya kembali dengan harga Rp {price} ribu. Berapa persen kah keuntungan yang ia dapat?", "pct"],
        ["{person} menjual {object} seharga Rp {price} ribu sedangkan ia memperolehnya dengan harga Rp {base} ribu. Berapa persen kah keuntungan yang ia dapat?", "pct"],
      ];
    }
    else { //hard
      $sentences = [
        ["{person} menjual {object} seharga Rp {price} ribu dan ia mendapat untung {pct}% dari harga pembeliannya. Berapakah harga pembelian {object} tersebut (dalam ribuan) ?", "base"],
        ["Jika sebuah {object} dijual seharga Rp {price} ribu, penjualnya akan mendapat untung {pct}%. Harga pembelian {object} tersebut (dalam ribuan) adalah ...", "base"],
        ["{person} menjual {object} dengan keuntungan sebesar Rp {diff} ribu. Jika keuntungan itu adalah {pct}% dari harga pembeliannya. Berapakah harga pembelian {object} tersebut (dalam ribuan) ?", "base"],
        ["{person} menjual {object} dengan keuntungan sebesar Rp {diff} ribu. Jika keuntungan itu adalah {pct}% dari harga pembeliannya. Berapakah harga penjualan {object} tersebut (dalam ribuan) ?", "price"],
      ];
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
