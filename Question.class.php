<?php

class Question {
  public $params;
  private $seed;

  public function __construct($context = [], $config = []) {
    if (isset($context['level'])) {
      $level = $context['level'];
    }
    else {
      $level = 1;
    }

    $this->seed = 0;
    if (isset($context['seed'])) {
      $this->seed = $context['seed'];
    }
    if ($this->seed == 0) {
      $this->seed = mt_rand(10000, 99999);  //5 digits number
    }
    mt_srand($this->seed);

    $this->params = $this->getRandomParameters($level);
    $this->params['seed'] = $this->seed;

    if (!isset($this->params['lang'])) {
      foreach (@($this->info())['tags'] as $lang => $info) {
        $this->params['lang'] = $lang;
        break;
      }
    }
  }

  public static function info() {
    return ['tags' => []];
  }

  protected function getRandomParameters($level) {
    return ['sentence' => ''];
  }

  protected function getSeedId($level='0', $lang='id') {
    if (!$level) $level = '0';
    $id = dechex($this->seed . $level . ($lang == 'en' ? '1' : '0'));
    return $id;
  }

	protected function createSentence($sentence, $params) {
		//e.g. $sentence = '{large} {tens} = {small} + _____';
		//e.g. $params = ['small' => $small, 'large' => $large, 'diff' => $diff, 'tens' => $tens];

		$exists = [];
		$newSentence = preg_replace_callback("/\{(\+?)(\w+)((?:\|\w+)?)\}/", function($m) use (&$exists, $params) {
			$exists[ strtolower($m[2]) ] = true;
			if (isset( $params[ strtolower($m[2]) ] )) {
				$str = $params[ strtolower($m[2]) ];
        if ($m[1] == '+') {
          $val = (int)$str;
          $str = ($val == 0 ? '' : ($val < 0 ? '-' : '+') . abs($val));
        }
        if ($m[3]) {
          $format = substr($m[3], 1);

        }
        return $str;
			}
			else {
				return $m[0];
			}
		}, (is_array($sentence) ? $sentence[0] : $sentence));

		if (is_array($sentence)) {
			$unknown = [$sentence[1]];
		}
		else {
			$unknown = [];
			foreach ($params as $par => $val) {
				if (!isset($exists[ strtolower($par) ])) {
					$unknown[] = $par;
				}
			}
		}
		return ['unknown' => $unknown, 'sentence' => $newSentence];
	}

	public function getFactors($num, $min=1) {
		if ($min < 1) $min = 1;
		$max = floor(sqrt($num));
		$factors = [];
		if ($min < $max) {
			for ($i=$min; $i<=$max; $i++) {
				if ($num % $i == 0) {
					$factors[ $i ] = true;
					$factors[ $num / $i ] = true;
				}
			}
			$factors = array_keys($factors);
			sort($factors);
		}
		return $factors;
	}


	public function exclude($arr, $exclude) {
		return array_values( array_diff($arr, $exclude) );
	}

}
