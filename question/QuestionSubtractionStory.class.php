<?php

include_once('Question.class.php');

class QuestionSubtractionStory extends Question {
	public function __construct($context = [], $config = []) {
		parent::__construct($context, $config);
  }

  public static function info() {
    return [
      'topic' => 'Addition/Subtraction Story',
      'tags' => [
        'en' => ['addition', 'plus', 'subtraction', 'minus', 'more than', 'less than', 'story']
      ]
    ];
  }

	protected function getRandomParameters($level = 0) {
		//$many - $few = $diff
		//$many + $few = $total
		$total = rand(200, 999);
		$many = rand(ceil($total/2) + 21, $total - 21);
		$few = $total - $many;
		$diff = $many - $few;

		$persons = [
			'generic' => [
				['Ryan', 'M'],
				['Alex', 'M'],
				['Jason', 'M'],
				['Sarah', 'F'],
				['Amanda', 'F',],
				['Roy', 'M'],
				['Jay', 'M'],
				['Martin', 'M'],
				['Fatin', 'M'],
				['Kate', 'F'],
				['Hasim', 'M'],
				['Hannah', 'F'],
				['Lee', 'M'],
				['Ken', 'M'],
				['Mike', 'M'],
				['Kim', 'M'],
				['Nur', 'F'],
				['Cindy', 'F'],
				['Ami', 'F'],
				['Taufiq', 'M']
			]
		];

		$verbs = [
			['has', 'have'],
			['collects', 'collect'],
			['buys', 'buy'],
		];

		$objects = [
			'small' => ['books', 'pencils', 'marbles', 'cards', 'paper kites', 'ice-cream sticks', 'lego bricks', 'stamps', 'beads']
		];

		$pidx = array_rand($persons['generic'], 2);
		$person = [$persons['generic'][$pidx[0]], $persons['generic'][$pidx[1]]];
		$verb = $verbs[ rand(0, sizeof($verbs) - 1) ];
		$object = $objects['small'][ rand(0, sizeof($objects['small']) - 1) ];
		$params = [
			'total' => $total,
			'many' => $many,
			'few' => $few,
			'diff' => $diff,
			'person1' => $person[0][0],
			'he1' => ($person[0][1] == 'M' ? 'he' : 'she'),
			'person2' => $person[1][0],
			'he2' => ($person[1][1] == 'M' ? 'he' : 'she'),
			'object' => $object,
			'has' => $verb[0],
			'have' => $verb[1],
			'lang' => 'en',
		];

		if ($level <= 1) {	//easy or medium
			$sentences = [
				["{person1} and {person2} {have} {total} {object} altogether.\nIf {person1} {has} {many} {object}, how many more {object} does {he1} {have} than {person2}?", "diff"],
				["{person1} and {person2} {have} {total} {object} altogether.\nIf {person1} {has} {many} {object}, how many {object} does {person2} {have}?", "few"],
				["{person1} and {person2} {have} {total} {object} altogether.\nIf {person1} {has} {few} {object}, how many less {object} does {he1} {have} than {person2}?", "diff"],
				["{person1} {has} {many} {object}.\n{person2} {has} {few} {object}. How many {object} do they {have} altogether?", "total"],
				["{person1} {has} {many} {object}.\n{person2} {has} {few} {object}. How many more {object} does {person1} {have} than {person2}?", "diff"],
				["{person1} {has} {many} {object}.\n{person2} {has} {few} {object}. How many less {object} does {person2} {have} than {person1}?", "diff"],
				["{person1} {has} {many} {object}.\n{person2} {has} {diff} {object} less than {person1}. How many {object} do they {have} altogether?", "total"],
				["{person1} {has} {few} {object}.\n{person2} {has} {diff} {object} more than {person1}. How many {object} do they {have} altogether?", "total"],
			];
		}	//hard
		else {
			$sentences = [
				["{person1} and {person2} {have} {total} {object} altogether.\nIf {person1} {has} {diff} more {object} than {person2}, how many {object} does {person1} {have}?", "many"],
				["{person1} and {person2} {have} {total} {object} altogether.\nIf {person1} {has} {diff} less {object} than {person2}, how many {object} does {person1} {have}?", "few"],
			];
		}


		$sentence = $sentences[ rand(0, sizeof($sentences) - 1) ];
		$result = $this->createSentence($sentence, $params);
		$params['sentence'] = $result['sentence'];
		$params['unknown'] = $result['unknown'];

//		error_log(var_export($params, true));

		return $params;

	}
}
