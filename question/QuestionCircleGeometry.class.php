<?php

include_once('Question.class.php');

class QuestionCircleGeometry extends Question {
	public function __construct($context = [], $config = []) {
		parent::__construct($context, $config);
  }

  public static function info() {
    return [
      'topic' => 'Lingkaran',
      'tags' => [
        'id' => ['lingkaran', 'geometri', 'luas', 'keliling']   //one word each
      ]
    ];
  }

	protected function getRandomParameters($level = 0) {
    //$perimiter = 2 * $pi * $r
    //$area = $pi * $r * r

    $iid = $this->getSeedId($level, 'id');
    $color = any(['blue', 'red', 'green', 'yellow', 'gray']);
		$params = [
      'iid' => $iid,
      'color' => $color,
			'lang' => 'id',
		];

    $shapes = array_values( preg_grep('/^shape\d+$/', get_class_methods($this)) );
    $shape = any( $shapes );
    $this->{$shape}($params, $level);

		$result = $this->createSentence($params['sentence'], $params);
		$params['sentence'] = "{http://latihun.com/line/ImageCircleGeometry.php?iid=".$iid."} ".$result['sentence'];
		$params['unknown'] = $result['unknown'];

		return $params;

	}


  private function shape1 (&$params, $level) {
    $shape1 = [ //ring
      'thick',
      'b_fc',
      'sqa_tl',
      'sqa_tr',
      'sqa_br',
      'sqa_bl',
      'xl+,yc-',
      'xr-,yc-',
      'xl+,yc+',
      'xr-,yc+',
      'thin',
      'border',
      'hl_lc',
      'hl_cr',
      'vl_tc',
      'vl_cb',
    ];

    $shape1_ = [  //flower
      'thick',
      'xl,yc,2,270,90',
      'xr,yc,2,90,270',
      'xc,yt,2,0,180',
      'xc,yb,2,180,0',
      'xc-,yc-',
      'xc+,yc-',
      'xc+,yc+',
      'xc-,yc+',
      'thin',
      'border',
      'hl_lc',
      'hl_cr',
      'vl_tc',
      'vl_cb',
    ];

    $shape2 = [ //face
      'thick',
      'b_fc',
      "sqa_tl",
      "sqa_tr",
      "hl_lc",
      "hl_cr",
      'xl+,yc-',
      'xr-,yc-',
      'xl+,yc+',
      'thin',
      'border',
      'vl_tc',
      'vl_cb',
    ];
    $shape2_ = [  //apple
      'thick',
      "xl,yc,2,270,0",
      "xc,yt,2,90,180",
      "xr,yc,2,180,270",
      "xc,yt,2,0,90",
      "xc,yc,2,0,180",
      "hl_lc",
      "hl_cr",
      'xc-,yc-',
      'xc+,yc-',
      'xc+,yc+',
      'thin',
      'border',
      'vl_tc',
      'vl_cb',
    ];

    $shape3 = [ //pacman
      'thick',
      'sqa_ctl',
      'sqa_cbl',
      'sqa_cbr',
      'hl_cr',
      'vl_tc',
      'xl+,yc-',
      'thin',
      'border',
      'hl_lc',
      'vl_cb',
    ];

    $shape3_ = [  //butterfly
      'thick',
      'xc,yt,xl,yt',
      'xc,yt,2,90,180',
      'xr,yc,2,90,180',
      'xr,yb,xr,yc',
      'xc,yc,2,270,0',
      'xc-,yt+',
      'thin',
      'border',
      'xl,yc,xr,yc',
      'xc,yt,xc,yb',
    ];

    $shape4 = [ //fox
      'thick',
      'xl,yt,xl,yc',
      'xc,yc,2,0,180',
      'xr,yc,xr,yt',
      'xr,yc,2,180,270',
      'xl,yc,2,270,0',
      'xc+,yc+',
      'thin',
      'border',
      'hl_lc',
      'hl_cr',
      'vl_tc',
      'vl_cb',
    ];

    $shape5 = [ //crescent
      'thick',
      'xc,yc,2,90,270',
      'xr,yc,2,90,270',
      'xc,yt,xr,yt',
      'xc,yb,xr,yb',
      'xc+,yt+',
      'thin',
      'border',
      'hl_lc',
      'hl_cr',
      'vl_tc',
      'vl_cb',
    ];

    if ($level == 0) {  //easy
      $type = mt_rand(1,4);
      if ($type == 1) {
        $params['feats'] = any([$shape1, $shape1_]);

        $r = mt_rand(1, 5);
        $d = 2 * $r;
        $a = 2 * $d;
        $perimeter = 3.14 * $a;

        $params['diameter'] = $d;
        $params['a'] = $a;
        $params['perimeter'] = $perimeter;
        $params['sentence'] = any([
          ["Keliling daerah yang diwarnai adalah aπ. Berapakah a?", 'a'],
          ["Berapakah keliling daerah yang diwarnai? (gunakan π = 3,14)", 'perimeter'],
        ]);
        $params['hint'] = "Rumus keliling lingkaran adalah dπ, yaitu diameter lingkaran dikali dengan π";
      }
      else if ($type == 2) {
        $params['feats'] = any([$shape3, $shape3_]);

        $r = mt_rand(1, 5) * 2;
        $d = 2 * $r;
        $a = 3/4 * $d;
        $b = $r + $r;
        $sum = $a + $b;
        $perimeter = ($a * 3.14) + $b;

        $params['diameter'] = $d;
        $params['a'] = $a;
        $params['sum'] = $sum;
        $params['perimeter'] = $perimeter;
        $params['sentence'] = any([
          ["Keliling daerah yang diwarnai adalah aπ + b. Berapakah a + b ?", 'sum'],
          ["Berapakah keliling daerah yang diwarnai? (gunakan π = 3,14)", 'perimeter'],
        ]);
        $params['hint'] = "Rumus keliling lingkaran adalah dπ, yaitu diameter lingkaran dikali dengan π";
      }
      else if ($type == 3) {
        $params['feats'] = any([$shape4]);

        $r = mt_rand(1, 5);
        $d = 2 * $r;
        $a = $r * $r;
        $area = 3.14 * $a;
        $perimeter = ($d * 3.14) + $d;

        $params['diameter'] = $d;
        $params['a'] = $a;
        $params['perimeter'] = $perimeter;
        $params['area'] = $area;
        if (mt_rand(0,1) == 0) {
          $params['sentence'] = any([
            ["Berapakah keliling daerah yang diwarnai? (gunakan π = 3,14)", 'perimeter'],
          ]);
          $params['hint'] = "Rumus keliling lingkaran adalah dπ, yaitu diameter lingkaran dikali dengan π";
        }
        else {
          $params['sentence'] = any([
            ["Luas daerah yang diwarnai adalah aπ. Berapakah a?", 'a'],
            ["Berapakah luas daerah yang diwarnai? (gunakan π = 3,14)", 'area'],
          ]);
          $params['hint'] = "Rumus luas lingkaran adalah πr² dimana r = d/2";
        }
      }
      else if ($type == 4) {
        $params['feats'] = any([$shape5]);

        $r = mt_rand(1, 5);
        $d = 2 * $r;
        $area = $r * $d;
        $perimeter = ($d * 3.14) + $d;

        $params['diameter'] = $d;
        $params['perimeter'] = $perimeter;
        $params['area'] = $area;
        if (mt_rand(0,1) == 0) {
          $params['sentence'] = any([
            ["Berapakah keliling daerah yang diwarnai? (gunakan π = 3,14)", 'perimeter'],
          ]);
          $params['hint'] = "Rumus keliling lingkaran adalah dπ, yaitu diameter lingkaran dikali dengan π";
        }
        else {
          $params['sentence'] = any([
            ["Berapakah luas daerah yang diwarnai? (gunakan π = 3,14)", 'area'],
          ]);
          $params['hint'] = "Rumus luas lingkaran adalah πr² dimana r = d/2";
        }
      }
    }
    else if ($level == 1) { //medium
      if (mt_rand(0, 1) == 0) {
        $params['feats'] = any([$shape2, $shape2_]);

        $d = mt_rand(1, 20) * 2;
        $a = 3/2 * $d;
        $b = $d;
        $sum = $a + $b;
        $perimeter = 3.14 * $a + $b;

        $params['diameter'] = $d;
        $params['a'] = $a;
        $params['sum'] = $sum;
        $params['perimeter'] = $perimeter;
        $params['sentence'] = any([
          ["Keliling daerah yang diwarnai adalah aπ + b. Berapakah a + b ?", 'sum'],
          ["Berapakah keliling daerah yang diwarnai? (gunakan π = 3,14)", 'perimeter'],
        ]);
        $params['hint'] = "Rumus keliling lingkaran adalah dπ, yaitu diameter lingkaran dikali dengan π";
      }
      else {
        $params['feats'] = any([$shape3, $shape3_]);
        $r = mt_rand(1, 10) * 2;
        $d = 2 * $r;
        $a = 3/4 * $r * $r;
        $area = 3.14 * $a;

        $params['diameter'] = $d;
        $params['a'] = $a;
        $params['area'] = $area;
        $params['sentence'] = any([
          ["Luas daerah yang diwarnai adalah aπ. Berapakah a?", 'a'],
          ["Berapakah luas daerah yang diwarnai? (gunakan π = 3,14)", 'area'],
        ]);
        $params['hint'] = "Rumus luas lingkaran adalah πr² dimana r = d/2";
      }
    }
    else { //hard
      if (mt_rand(0, 1) == 0) {
        $params['feats'] = any([$shape1, $shape1_]);
        //ca = pi*r2
        //ia = d*d - pi*r2
        //sa = pi*r2 - (d*d - pi*r2) = 2*pi*r2 - d*d
        $r = mt_rand(1, 7);
        $d = 2 * $r;
        $a = 2 * $r * $r;
        $b = $d * $d;
        $sum = $a + $b;
        $area = 3.14 * $a - $b;

        $params['diameter'] = $d;
        $params['sum'] = $sum;
        $params['area'] = $area;
        $params['sentence'] = any([
          ["Luas daerah yang diwarnai adalah aπ - b. Berapakah a + b ?", 'sum'],
          ["Berapakah luas daerah yang diwarnai?", 'area'],
        ]);
        $params['hint'] = "Rumus luas lingkaran adalah πr² dimana π adalah 3,14 dan r = d/2. Untuk menghitung luas bagian putihnya, coba gunakan luas seperempat bujur sangkar dikurangi luas seperempat bagian lingkaran.";
      }
      else {
        $params['feats'] = any([$shape2, $shape2_]);
        //ca = pi*r2
        //ia = (d*d - pi*r2) / 2
        //sa = pi*r2 - (d*d - pi*r2) / 2 = 3/2*pi*r2 - d*d/2
        $r = mt_rand(1, 7);
        $d = $r * 2;
        $area = (3 / 2 * 3.14 * $r * $r) - ($d * $d / 2);

        $params['diameter'] = $d;
        $params['area'] = $area;
        $params['sentence'] = any([
          ["Berapakah luas daerah yang diwarnai? (gunakan π = 3,14)", 'area'],
        ]);
        $params['hint'] = "Rumus luas lingkaran adalah πr² dimana r=d/2. Luas lubang bagian tengah sama dengan setengah dari luas bujur sangkar dikurangi luas lingkaran.";
      }
    }
  }
}
