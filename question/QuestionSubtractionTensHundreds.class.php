<?php

include_once('Question.class.php');

class QuestionSubtractionTensHundreds extends Question {
	public function __construct($context = [], $config = []) {
		parent::__construct($context, $config);
  }

  public static function info() {
    return [
      'topic' => 'Addition/Subtraction',
      'tags' => [
        'en' => ['addition', 'plus', 'subtraction', 'minus', 'more than', 'less than']
      ]
    ];
  }

  protected function getRandomParameters($level = 0) {
		//$large - $small = $diff
		$sentences = [];
		if ($level == 0) {	//easy
			$zeroes = 1;
			$withZero = 'small';
			$small = rand(1, 5);
			$large = rand($small + 21, 299);
			$diff = $large - ($small * 10);
		}
		else {	//medium or hard
			$zeroes = rand(1, 2);	//1=tens, 2=hundreds
			$withZero = rand(0, 1) ? 'large' : 'small';
			if ($withZero == 'small') {	//small
				if ($zeroes == 1) {	//tens
					$small = rand(6, 60);
					$large = rand(($small * 10) + 21, 999);
					$diff = $large - ($small * 10);
				}
				else {	//hundreds
					$small = rand(2, 6);
					$large = rand(($small *100) + 21, 999);
					$diff = $large - ($small * 100);
				}
			}
			else {	//large
				if ($zeroes == 1) {	//tens
					$large = rand(6, 90);
					$small = rand(21, ($large * 10) - 21);
					$diff = ($large * 10) - $small;
				}
				else {	//hundreds
					$large = rand(2, 9);
					$small = rand(21, ($large * 100) - 21);
					$diff = ($large * 100) - $small;
				}
			}
		}


		$tens = ($zeroes == 1 ? 'tens' : 'hundreds');
		if ($withZero == 'small') {	//small
			$sentences = [
				'{large} - {small} {tens} = _____',
				'{large} - _____ {tens} = {diff}',
				'{large} = {small} {tens} + _____',
				'{large} = _____ {tens} + {diff}',
				'Subtract {small} {tens} from {large}',
				'{small} {tens} subtract from {large} is _____',
				'_____ {tens} subtract from {large} is {diff}',
				'{diff} + _____ {tens} = {large}',
				'_____ + {small} {tens} = {large}',
				'{small} {tens} less than {large} = _____',
				'_____ {tens} less than {large} = {diff}',
				'{diff} less than {large} = _____ {tens}',
				'_____ less than {large} = {small} {tens}',
				'_____ more than {small} {tens} is {large}',
				'{diff} more than _____ {tens} is {large}',
				'_____ {tens} more than {{diff}} is {large}',
				'{small} {tens} more than _____ is {large}',
			];
		}
		else {	//large
			$sentences = [
				'{large} {tens} - {small} = _____',
				'{large} {tens} - _____ = {small}',
				'Subtract {small} from {large} {tens}',
				'{small} subtract from {large} {tens} is _____',
				'_____ subtract from {large} {tens} is {small}',
				'{small} + _____ = {large} {tens}',
				'_____ + {small} = {large} {tens}',
				'{large} {tens} = {small} + _____',
				'{small} less than {large} {tens} = _____',
				'_____ less than {large} {tens} = {small}',
				'_____ more than {small} is {large} {tens}',
				'{small} more than _____ is {large} {tens}',
			];
		}

		$params = [
      'small' => $small,
      'large' => $large,
      'diff' => $diff,
      'tens' => $tens,
      'lang' => 'en',
    ];
		$sentence = $sentences[ rand(0, sizeof($sentences) - 1) ];
		$result = $this->createSentence($sentence, $params);
		$params['sentence'] = $result['sentence'];
		$params['unknown'] = $result['unknown'];

//		error_log(var_export($params, true));

		return $params;
	}
}
