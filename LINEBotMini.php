<?php

require_once('./LINEBotTiny.php');

class LINEBotMini extends LINEBotTiny {

    public function pushMessage($message)
    {
        $header = array(
            "Content-Type: application/json",
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => json_encode($message),
            ),
        ));

        $response = file_get_contents('https://api.line.me/v2/bot/message/push', false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }
    }

    public function getProfile($userId)
    {
        $header = array(
            "Content-Type: application/json",
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => implode("\r\n", $header),
                "content" => '{}',
            ),
        ));

        $response = file_get_contents('https://api.line.me/v2/bot/profile/' . $userId, false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }

        $data = json_decode($response, true);
        if (!isset($data['userId'])) {
            http_response_code(400);
            error_log("Invalid response body: missing userId property");
            exit();
        }
        return $data;
    }

    public function sendResponses($responses, $userId, $replyToken='') {
        if (empty($responses)) {
            return;
        }

        $count = 0;
        foreach ($responses as $response) {
            $text = '';
            $delay = 0;
            $type = 'text';
            $url = '';
            if ((is_string($response) || is_numeric($response)) && ($response || $response === 0 || $response === '0')) {
                $text = $response;
            }
            else if (is_array($response) && count($response) > 0 && (is_string($response[0]) || is_numeric($response[0])) && ($response[0] || $response[0]===0 || $response[0] === '0')) {
                $text = $response[0];
                $delay = (int)@$response[1];  //in seconds
                if (isset($response[2])) {
                  $type = $response[2];
                }
            }
            if ($text || $text === 0 || $text === '0') {
                if ($delay > 0) {
                  usleep($delay * 1000000); //delay to simulate typing or thinking
                }

                if (preg_match("/\{(https?:.+?)\}/", $text, $match)) {
                  $newtext = trim(preg_replace("/\{https?:.+?\}\s*/", "", $text));
                  $url = $match[1];
                  $messages = [['type' => 'image', 'originalContentUrl' => $url, 'previewImageUrl' => $url]];
                  if ($newtext) {
                    $messages[] = ['type' => 'text', 'text' => $newtext];
                  }
                }
                else if (preg_match("/\((\w+|\d+,\d+)\)/", $text, $match)) {
                  $newtext = trim(preg_replace("/\((\w+|\d+,\d+)\)\s*/", "", $text, 1));
                  if (preg_match("/^\w+$/", $match[1])) {
                    $sticker = $this->sticker( $match[1] );
                  }
                  else {
                    $sticker = explode(",", $match[1]);
                  }

                  $messages = [];
                  if (!empty($sticker)) {
                    $messages[] = ['type' => 'sticker', 'packageId' => $sticker[0], 'stickerId' => $sticker[1]];
                  }
                  else {
                    $newtext = $text;
                    if (preg_match("/^\w+$/", $match[1])) {
                      $emoji = $this->emoji( $match[1] );
                      if ($emoji) {
                        $newtext = trim(preg_replace("/\(\w+\)/", $emoji, $text, 1));
                      }
                    }
                  }
                  if ($newtext) {
                    $messages[] = ['type' => 'text', 'text' => $newtext];
                  }
                }
                else if ($type == 'image' && !$url && preg_match("/^https/", $text)) {
                  $messages = [['type' => 'image', 'originalContentUrl' => $text, 'previewImageUrl' => $text]];
                }
                else if ($text == 'testbtn') {
                  $messages = [[
                    'type' => 'template',
                    'altText' => 'Test',
                    'template' => [
                      'type' => 'buttons',
                      'thumbnailImageUrl' => 'https://tplen.com/line/sampleimg.jpg',
                      'text' => " \xF0\x9F\x98\x83 🌕 ",
                      'actions' => [[
                        'type' => 'message',
                        'label' => 'sss',
                        'text' => 'Hi',
                      ]]
                    ]
                  ]];
                }
                else if ($text == 'testmap') {
                  $messages = [[
                    'type' => 'imagemap',
                    'baseUrl' => 'https://tplen.com/line/sampleimg.jpg?size=',
                    'altText' => 'Test',
                    'baseSize' => [
                      'width' => 1040,
                      'height'=> 767,
                    ],
                    'actions' => [[
                      'type' => 'message',
                      'text' => 'Hi',
                      'area' => [
                        'x' => 0,
                        'y' => 0,
                        'width' => 500,
                        'height' => 500,
                      ]
                    ]]
                  ]];
                }
                else {
                  $messages = [['type' => 'text', 'text' => $text]];
                }
                if ($count == 0 && $replyToken) {
                  //use Reply API for the first message
                  $this->replyMessage(array(
      							'replyToken' => $replyToken,
                    'messages' => $messages
                  ));
                }
                else {
                  //use Push API for the rest of messages
                  $this->pushMessage(array(
                    'to' => $userId,
                    'messages' => $messages
                  ));
                }
                log_conversation($userId, "< ".$text);
                $count++;
            }
        }
    }

    public function sticker($name) {
        $stickers = [
          'amazed' => [1, 4],		//cute face with stars
          'sing' => [1, 11],	//sing with microphone
          'yeah' => [1, 114],	//fist in the air
          'yes' => [2, 40],	//yes! text in cloud
          'hey' => [2, 34],	//head comes out from a hole, smiling
          'okay' => [2, 179],	//okay! banner with 2 hands
          'dance' => [2, 501],	//girl bear dance hoola hoop
					'hehe' =>	[1, 107],	//hehe, scratch head with cheeky tongue
					'hmm' => [2, 22],	//hmm, wear black eyeglasses, fingers at chin
					'huh' =>	[2, 149],	//bear with question marks above its head
          'popcorn' => [1, 402],  //watch movie while eating popcorn
					'no' => [2, 39],	//no with hard exclamation
					'study' => [2, 30],	//study reading a book
					'thankyou' => [2, 41],	//thank you colorful text
          'drool' => [1, 1],  //sleep while drooling
          'yawn' => [1, 405],  //yawning
          'gogo' => [1, 406],  //go! go!
          'sunshine' => [4, 263], //sunshine smile
          'cloudy' => [4, 264], //cloudy
          'star' => [4, 267], //star shine smile
          'gift' => [4, 608], //wrapped gift
          'bulb' => [4, 275], //light bulb
          'car' => [4, 275], //red car
          '5min' => [4, 616], //5 min bubble
          '10min' => [4, 617], //10 min bubble
          'thumbup' => [4, 293], //thumb up
          'muscle' => [4, 294], //muscle up
          'clap' => [4, 296], //hand claps
          'icecream' => [4, 297], //ice cream
          'coffee' => [4, 298], //coffee
          'tea' => [4, 299], //tea
          'rice' => [4, 302], //rice
          'pizza' => [4, 304], //pizza
          'cake' => [4, 307], //cake
        ];

        return (isset($stickers[$name]) ? $stickers[$name] : '');
    }

    public function emoji($name) {
        $emoji = [
          'smile' => "\xF0\x9F\x98\x83",  // smiling face with open mouth 😃
          'coin' => "", //𝕆
        ];


        return (isset($emoji[$name]) ? $emoji[$name] : '');
    }

}
