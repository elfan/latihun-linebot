<?php

//setup data access
require_once('db.php');
require_once('tools.php');
$LINE = setupLINEBot();
$redis = setupRedis();

define("TZ", 8 * 60 * 60);

//alert any code error to admin's LINE
$adminId = "<line_user_id>";

include_once("log_handler.php");

//log all requests
log_request();

define("REDIS_EXP", 24 * 60 * 60);  //1 day

//main logic: handle incoming events
foreach ($LINE->parseEvents() as $event) {
    if ($event['source']['type'] == 'user') {
      $userId = @$event['source'][ 'userId' ];
      $responses = handleEvent($event, $userId);
      $LINE->sendResponses($responses, $userId, (isset($event['replyToken']) ? $event['replyToken'] : ''));
    }
    else {
      debug("****************** Unsupported source ID: " . @$event['source']['type']);
    }
}


function setupLINEBot() {
  require_once('./LINEBotMini.php');

  $channelAccessToken = 'hWzQITB99Tb+P/SKL3EGGm2f6FBE9D5kwEb0MyPtjYKWLA4ZXF78BZWjawqBcHENGzwdfvRVcvvdx4w3nED6Wsd2be8FlrUbCRM4Mdb9ov2mvwDbqHdFQk2Tc5Z6mO4xd+N/KxBRX9H83c3K+K7h+AdB04t89/1O/w1cDnyilFU=';

  $channelSecret = 'fd7bd69cc98643bb63f0d25e3225b682';

  return new LINEBotMini($channelAccessToken, $channelSecret);
}


function setupRedis() {
  $redis = new Redis(); 
  $redis->connect('127.0.0.1', 6379); 
  return $redis;
}


function handleEvent($event, $userId) {
    switch ($event['type']) {
        case 'message':
            $message = $event['message'];
            switch ($message['type']) {
                case 'text':  //id, type, text
                    log_conversation($userId, ": ".$message['text']);
                    return handleTextMessageEvent($message['text'], $userId);

                case 'sticker': //id, type, packageId, stickerId
                    $text = "(".$message['packageId'].",".$message['stickerId'].")";
                    log_conversation($userId, ": ".$text);
                    return handleTextMessageEvent($text, $userId);

                case 'image': //id, type
                case 'video': //id, type
                case 'audio': //id, type
                case 'location':  //id, type, title, address, latitude, longitude
                default:
                    debug("****************** Unsupported message type: " . $message['type']);
            }
            break;

        case 'follow':
            log_conversation($userId, "! new follower");
            return handleFollowEvent($userId);
            break;

        case 'unfollow':
            log_conversation($userId, "! unfollow");
            return handleUnfollowEvent($userId);
            break;

        case 'join':
        case 'leave':
        case 'postback':
        case 'beacon':
    }
}

function handleFollowEvent($userId, $targetId="") {
    global $LINE;

    if (!$targetId) {
      $targetId = $userId;
    }

    $info = $LINE->getProfile($targetId);
    if ($info['displayName']) {
      vset('user/'.$targetId.'/display_name', $info['displayName']);
    }
    if ($info['pictureUrl']) {
      vset('user/'.$targetId.'/picture_url', $info['pictureUrl']);
    }

    $nick = vget('user/'.$targetId.'/nick_name');

    if (!$nick) {
      $response[] = ["Hai", 1];
      $nick = ($info['displayName'] ? $info['displayName'] : substr($targetId, 0, 5));
    }
    else {
      $response[] = ["Hai " . $nick, 1];
    }

    vset('user/'.$userId.'/asked:greeting', true);

    if (date("Y-m-d") <= "2017-04-06") {
      vset('user/'.$userId.'/promo_beta_apr2017', time());
    }

    $id = substr($targetId, 0, 5);

    vdel('user/'.$targetId.'/unfollow');
    notifyAdmin("Ada user baru: " . $id . "(".$nick.")");
    return $response;
}

function handleUnfollowEvent($userId) {
    vdel('user/'.$userId.'/asked:*'); //forget all my questions
    $nick = vget('user/'.$userId.'/nick_name');
    if (!$nick) {
      $nick = substr($userId, 0, 5);
    }
    vset('user/'.$userId.'/unfollow', true);
    notifyAdmin("User pergi: " . $nick);
}

function handleTextMessageEvent($text, $userId) {
    global $adminId;

    $response = [];

    $saya = "(?:aku|sa?ya?|gw|gu[ae])";
    $kamu = "(?:ka?mu?|kakak?|om|mas|bang|antum|awak|loe|lu)";

    //block long spam msg
    $text = trim($text);
    if (strlen($text) > 200) {
      if (rand(0, 4) == 0) {
        $response[] = any([
            "Tolong jangan kirim pesan panjang-panjang",
            "Kepanjangan",
            "Ngga dibaca kalau panjang"
        ]);
      }
      return $response;
    }
    $oriText = $text;

    if (vget("user/".$userId."/asked:want_question")) {
      if (preg_match("/\b((ng)?gak?( *mau)?|ogah|no)\b/i", $text, $match)) {
        vdel("user/".$userId."/asked:want_question");
        $response[] = "Ok";
        $response[] = ['Kalau nanti mau, tinggal bilang aja "minta soal" yah', 1];
      }
      else if (preg_match("/\b(mau|tentu|boleh|ok|i?ya|yes|sure)\b/i", $text, $match)) {
        vdel("user/".$userId."/asked:want_question");

        if (vget('user/'.$userId.'/promo_beta_apr2017')
            && (date("Y-m-d") <= "2017-04-06")
            && ((vget("user/".$userId."/count_try") == 0 && vget("user/".$userId."/count_guess") == 0) || rand(0, 1) == 0)) {
          $response[] = "Psst! Kalau kamu bisa jawab 25 soal paling lambat sampai tgl 6 April 2017, kamu bisa dapat hadiah pulsa sebesar Rp 25.000 lho, hanya untuk 10 orang pertama";
        }

        $response[] = ["Ok, siap ya...", 1];
        $response[] = [pickQuestion($userId), 1]; //new module and its achieved level
      }
    }
    else if (vget('user/'.$userId.'/important_asked:phone_number')) {
      if (preg_match("/\b(\+?\d{8,15})\b/i", $text, $match)) {
        vset('user/'.$userId.'/phone_number', $match[1]);
        vdel('user/'.$userId.'/important_asked:phone_number');
        notifyAdmin("User ". substr($userId, 0, 5) . " HP " . $match[1]);
        $response[] = "Ok, nanti pulsanya akan diisi secepatnya";
      }
      else {
        if (rand(0, 5) == 0) {
          $response[] = "Berapa nomor HP untuk menerima hadiah pulsa Rp 25.000?";
        }
      }
    }

    if (count($response) > 0) {
      vset('user/'.$userId.'/last_contact_time', time());
      vset('user/'.$userId.'/last_message', $oriText);
      return $response;
    }


    if (preg_match("/\b(ba?ha?sa?|inggris|english|seni( *budaya)?|indonesia|ipa|ips|biologi|fisika|selain +ma?t(e?matika?)?)\b/i", $text, $match)) {
      $response[] = ucfirst(any([
        "/(untuk )?(saat ini|sekarang) hanya( ada)?( soal)? *?matematika? s?aja/",
      ]));
    }
    else if (preg_match("/^\b(ha?i|h[ae][iy]|h[ae]l+o|o+y|pagi|siang|sore|mal[ae]m)\b/i", $text, $match)) {
        if (vget('user/'.$userId.'/asked:greeting')) {
          vdel('user/'.$userId.'/asked:greeting');
        }
        else {
          $response[] = ucfirst(strtolower($match[1])) . " juga";
        }

        if (!vget('user/'.$userId.'/nick_name')) {
          $response[] = [askNickName($userId), 1];
        }
        else {
          $response[] = [offerQuestion($userId), 1];
        }
    }
    else if (preg_match("/^(test?|ping)\b/i", $text, $match)) {
        $response[] = any([
          ucfirst(strtolower($match[1])) . " juga",
          ucfirst(strtolower($match[1])) . " diterima",
          "1 2 3",
          "Satu dua tiga",
        ]);
    }



    ////////////////// DEV MODE
    else if ($userId == $adminId && preg_match("/^dump$/i", $text, $match)) {
        $response[] = vdump();
    }
    else if ($userId == $adminId && preg_match("/^((refresh +)?)modules$/i", $text, $match)) {
        $response[] = listModules($match[1]);
    }
    else if ($userId == $adminId && preg_match("/^rand$/i", $text, $match)) {
        $response[] = testRandom();
    }
    else if ($userId == $adminId && preg_match("/^star$/i", $text, $match)) {
        $response[] = "star ★ star ";
    }
    else if ($userId == $adminId && preg_match("/^((be?ra?pa?|list) +(or(an)?g|user)|user)\b/i", $text, $match)) {
        $response[] = listUser();
    }
    else if ($userId == $adminId && preg_match("/^(?:say|bilang ke) +(u[a-f0-9]{1,}) +(.+)$/i", $text, $match)) {
        $response[] = sendUser($match[1], $match[2]);
    }
    else if ($userId == $adminId && preg_match("/^info +(u[a-f0-9]{1,})$/i", $text, $match)) {
        $response = array_merge($response, getUserInfo($match[1]));
    }
    else if ($userId == $adminId && preg_match("/^duel$/i", $text, $match)) {
        $response = array_merge($response, handleChallengeMode($userId));
    }
    else if ($userId == $adminId && preg_match("/^set promo\W*$/i", $text, $match)) {
        vset('user/'.$userId.'/promo_beta_apr2017', time());
        $response[] = "Ok ".date("Y-m-d");
    }
    else if ($userId == $adminId && preg_match("/^follow\W*$/i", $text, $match)) {
        return handleFollowEvent($userId, "Ub6c912ec55b8b7cf522be16fdfc94988");
    }
    else if ($userId == $adminId && preg_match("/^(testbtn|testmap)\W*$/i", $text, $match)) {
        $response[] = strtolower($match[1]);
    }
    else if ($userId == $adminId && preg_match("/^\(.+?\)$/i", $text, $match)) {
        $response[] = strtolower($text);
    }
    else if ($userId == $adminId && preg_match("/^circle\W*$/i", $text, $match)) {
        $module = "QuestionCircleGeometry";
        include_once('question/' . $module . '.class.php');
        $context = ["level" => 0, "lang" => "id"];
        $q = new $module($context);
        $response[] = $q->params['sentence'];
    }
    else if ($userId == $adminId && preg_match("/^exportdb$/i", $text, $match)) {
        exportDb();
        $response[] = "Ok";
    }
    else if ($userId == $adminId && preg_match("/^cleanemptylevel$/i", $text, $match)) {
        cleanEmptyLevel();
        $response[] = "Ok";
    }

    ////////////////////




    //HELP
    else if (preg_match("/^\W*(help|tolong)\W*$/i", $text, $match)) {
        if (recently('user/'.$userId.'/last_help_time', 30)) {
          if (!recently('user/'.$userId.'/last_contact_time', 4)) {
            $response[] = any([
              "Udah dijawab barusan",
              "Baru aja tadi dikasih tau",
              "Coba liat lagi di atas",
            ]);
          }
        }
        else {
          $response[] = helpMenu();
          vset('user/'.$userId.'/last_help_time', time());
        }
    }

    //TOPIC
    else if (preg_match("/\b(^topik$|(topik|bab|module?|soal) +apa( +s?aja)?|da?fta?r +(topik|bab|module?|soal) *(nya)?)\b/i", $text, $match)) {
        if (recently('user/'.$userId.'/last_topic_time', 30)) {
          if (!recently('user/'.$userId.'/last_contact_time', 4)) {
            $response[] = any([
              "Udah dijawab barusan",
              "Baru aja tadi dikasih tau",
              "Coba liat lagi di atas",
            ]);
          }
        }
        else {
          $topics = explode(",", vget("system/0/question_topics/id"));
          $response[] = any([
            "Daftar topik saat ini:",
            "Ada ".any(["soal", "topik"])." tentang",
            "Saat ini baru ada ".any(["soal", "topik"])." tentang"
          ]) . " " . implode(", ", $topics);
          vset('user/'.$userId.'/last_topic_time', time());
        }
    }

    //FEEDBACK
    else if (preg_match("/^\W*((".$saya." +)?((mau( +(ngasih|kasih))?|punya) +)?)?(usul(an)?|minta|saran) +(dong +)?(soal *nya +)?((su?pa?ya?|biar|bikin|(mem)?buat|fitur|gi?ma?na?|topik) +.+)$/i", $text, $match)) {
        $feedback = preg_replace("/[`~\r\n]/", " ", $text);
        debug($userId. " feedback: " .$feedback, "feedback.txt");
        $response[] = any([
          "Terima kasih atas usulannya",
          "Ok, ma kasih, nanti dipertimbangkan",
          "Ma kasih masukannya",
        ]);
        $response[] = "Kalau ada masalah atau mau tanya-tanya, hubungi aja @enof pake LINE";
    }

    //GUIDE
    else if (preg_match("/\b(ajarin|rumus( *nya)?|(cara *nya +)?(bagai|gi?)ma?na?|pe?njelasan( *nya)?|petunjuk( *nya)?|hint|(pem)?bahas(an( *nya)?)?|cara( +menyelesaikan| +nyeles[ae]in)? *nya|cara)\b/i", $text, $match)) {
        if (recently('user/'.$userId.'/last_hint_time', 1 * 60)) {
          $response[] = any(["Maaf yah, hanya boleh minta rumus sekali aja setiap 1 menit", "Belum ada semenit yg lalu saya kasih tau rumusnya"]);
        }
        else {
          $params = json_decode(vget("user/".$userId."/current_question_context"), true);
          if (isset($params['hint'])) {
            $hints = (is_array($params['hint']) ? $params['hint'] : [ $params['hint'] ]);
            foreach ($hints as $hint) {
              $response[] = $hint;
            }
            vset('user/'.$userId.'/last_hint_time', time());
          }
          else {
            $response[] = "Maaf belum bisa ngasih rumus buat yg ini";
          }
        }
    }

    //GIVE UP
    else if (preg_match("/^((".$saya." +)?nyerah|(be?r)?apa +(sih +)?(ja?wa?ba?n?nya|kuncinya)|((be?r)?apa +)?kunci( *nya)?|ja?wa?ba?n? *nya +((be?r)?apa|(bagai|gi?)ma?na?)|be?ra?pa? +ja?wa?b(an)?nya)\b/i", $text, $match)) {
        $response = array_merge($response, handleGiveUp($userId));
    }

    //GIVE UP
    else if (preg_match("/\b(".$kamu." +tah?u +(ng)?gak?)\b/i", $text)) {
        $response[] = any(['Tau dong (smile)', 'Tentu aja (smile)']);
    }

    //GIVE UP
    else if (preg_match("/^((be?r)?apa|be?ra?pa?)\b/i", $text, $match) && preg_match("/\b(".$kamu." +tah?u +(ng)?gak?)\b/i", vget('user/'.$userId.'/last_message'))) {
        $response = array_merge($response, handleGiveUp($userId));
    }

    //WHERE
    else if (preg_match("/\b(di?ma?na?|(di|da?ri?) *mana)\b/i", $text, $match)) {
        $response[] = "Di awan";
        $place = vget('user/'.$userId.'/place');
        if (!$place) {
          $response[] = ["Kalau kamu di mana?", 1];
          vdel('user/'.$userId.'/asked:*');
          vset('user/'.$userId.'/asked:place', true);
        }
    }
    else if (preg_match("/^\W*((ka)?kak|kaka|om|bang|mas|dhiqa?)\W*$/i", $text, $match)) {
        $lang = vget('user/'.$userId.'/lang');
        if ($lang == 'en') {
          $response[] = "Yes?";
        }
        else {
          $response[] = any(["Iya?", "Iya", "Apa", "Apa?"]);
        }
    }
    else if (preg_match("/\bkamu +adik(nya)? +chiqa\b/i", $text, $match)) {
        $response[] = any(["Iya saya adiknya Chiqa :)", "Iya, Chiqa itu kakak saya :)"]);
    }
    else if (preg_match("/\bkamu +kakak?(nya)? +chiqa\b/i", $text, $match)) {
        $response[] = any(["Bukan, saya adiknya Chiqa :)", "Bukan, Chiqa itu kakak saya :)"]);
    }

    //WHO ARE YOU
    else if (preg_match("/\b(".$kamu." +siapa|na?ma?(?: +".$kamu."| *mu)? +(si)?apa|(si)?apa( +na?ma?)?(?: +".$kamu."| *mu))\b/i", $text, $match)) {
        $response[] = any([
          "Dhiqa",
          "Nama saya Dhiqa",
        ]);
        $response[] = askNickName($userId);
    }
    else if (preg_match("/\b((".$kamu."|ini) +((chat *)?bot|otomatis))\b/i", $text, $match)) {
        $response[] = any([
          "Iya, saya chat bot",
          "Iya",
        ]);
    }

    //WHO AM I
    else if (preg_match("/\b(na?ma?( *ku| +".$saya.")? +(si)?apa|".$saya." +(si)?apa|(si)?apa( +na?ma?)".$saya."|(si)?apa +".$saya.")\b/i", $text, $match)) {
        $nick = vget('user/'.$userId.'/nick_name');
        if ($nick) {
          $response[] = $nick;
          $place = vget('user/'.$userId.'/place');
          if ($place && rand(0,1)==0) {
            $response[] = ["di " . $place, 1];
          }
        }
        else {
          $response[] = "Kamu belum ngasih tahu";

          $response[] = askNickName($userId);
        }
    }

    //FORGET MY NAME
    else if (preg_match("/\blupakan +na?ma?( *ku| +".$saya.")\b/i", $text, $match)) {
        vdel('user/'.$userId.'/nick_name');
        $response[] = any([
          "Ok",
          "Ok, sudah lupa",
          "Baiklah",
          "Sudah lupa sekarang",
          "Sekarang sudah lupa",
        ]);
    }

    //MY NAME IS
    else if (preg_match("/\bna?ma?(?: *ku| +".$saya.") +([a-z]{3,10})\b/i", $text, $match)) {
        $nick = ucfirst(strtolower($match[1]));
        vset('user/'.$userId.'/nick_name', $nick);
        vdel('user/'.$userId.'/asked:nick_name');
        $response[] = "Ok " . $nick;
    }

    //REPEAT QUESTION
    else if (preg_match("/\b(ulangi|(soal|pertanyaan)nya +apa|apa( +.*)? +(soal|pertanyaan)nya|repeat|last +question)\b/i", $text, $match)) {
        $response[] = askQuestion($userId);
    }


    //NEW QUESTION with possibly specific topic and level jump
    else if (preg_match("/\b(latihan(nya)?|soal(an)?(nya)?|pertanyaan(nya)?|tanya +".$saya.")\b/i", $text, $match)) {
        $text = preg_replace("/\b(latihan(nya)?|soal(an)?(nya)?|pertanyaan(nya)?|tanya +".$saya.")\b/i", "", $text);
        $set = setLangLevelTags($userId, 'id', $text);
        $response[] = pickQuestion($userId, $set['level']); //override level if any
    }
    else if (preg_match("/\b(math|question|ask +me)\b/i", $text, $match)) {
        $text = preg_replace("/\b(math|question|ask +me)\b/i", "", $text);
        $set = setLangLevelTags($userId, 'en', $text);
        $response[] = pickQuestion($userId, $set['level']); //override level if any
    }

    //SKIP to a new question, with any topic and level
    else if (preg_match("/\b(soal(an)?( +y(an)?g)? +lain|y(an)?g +lain|skip|ganti|another +one|next)\b/i", $text, $match)) {
        $response[] = pickQuestion($userId);  //with new module and its achieved level
    }

    //LEVEL UP, same topic
    else if (preg_match("/\b(terlalu +(mudah|gampang)|^\W*(mudah|gampang) +(amat|ba?nget|se?kali)|y(an)?g( +(lebih|agak))? +(susah|sulit)|(lebih|agak) +(susah|sulit))\b/i", $text, $match)) {
        $response = array_merge($response, changeLevel($userId, 'up'));
    }
    else if (preg_match("/\b(too +easy|(hard|difficult) +one|harder|more difficult)\b/i", $text, $match)) {
        $response = array_merge($response, changeLevel($userId, 'up'));
    }

    //LEVEL DOWN, same topic
    else if (preg_match("/\b(terlalu +(susah|sulit)|^\W*(susah|sulit) +(amat|ba?nget|se?kali)|y(an)?g( +(lebih|agak))? +(mudah|gampang)(an)?|(lebih|agak) +(mudah|gampang)(an)?)\b/i", $text, $match)) {
        $response = array_merge($response, changeLevel($userId, 'down'));
    }
    else if (preg_match("/\b(too +(hard|difficult)|easy +one|easier|less difficult)\b/i", $text, $match)) {
        $response = array_merge($response, changeLevel($userId, 'down'));
    }


    //SCORE
    else if (preg_match("/\b(skor|nilai|point?|score)\b/i", $text, $match)) {
        if (preg_match("/\brincian\b/i", $text, $match)) {
          $response[] = infoScore($userId, true);
        }
        else {
          $response[] = infoScore($userId);
        }
    }
    else if (preg_match("/\b(rinciannya|^\W*rincian\W*$)\b/i", $text)) {
        if (preg_match("/\b(skor|nilai|point?|score)\b/i", vget('user/'.$userId.'/last_message'))) {
          $response[] = infoScore($userId, true);
        }
        else {
          $response[] = 'Rincian apa';
        }
    }


    //STAR
    else if (preg_match("/\b(bintang +itu +((artinya|maksudnya) +)?apa)\b/i", $text, $match)) {
        $response[] = "Jumlah bintang itu menunjukkan tingkat kesukaran";
        $response[] = ["Satu bintang artinya mudah, dua artinya menengah, tiga artinya susah", 1];
    }

    //FRUSTATION
    else if (preg_match("/\b(susah|aduh|apa *yah?|bingung|binun|(ng)?gak?( *(tah?u+|bi?sa+))?)\b/i", $text, $match)) {
        $str = '';
        if (rand(0,1)==0) {
          $str = 'Ayo coba lagi';
        }
        $response[] = $str;
        if (!$str || rand(0,2)==0) {
          $response[] = any([
            'Jangan menyerah',
            'Jangan putus asa',
            'Kamu pasti bisa',
          ]);
        }
    }
    else if (preg_match("/\b(hard|don'?t +know)\b/i", $text, $match)) {
        $str = '';
        if (rand(0,1)==0) {
          $str = 'Please try again';
        }
        $response[] = $str;
        if (!$str || rand(0,2)==0) {
          $response[] = [["Don't give up", 'You can do it', 'Come on!'][rand(0,2)], 1];
        }
    }


    //ANSWER
    else if (preg_match("/^\s*(?:ja?wa?b(?:a?n)? *nya|ja?wa?b *:)? *(-?(\d+\/\d+|\d+[,.]\d+|\d+[,.]|[,.]\d+|\d+))( *[a-z ?]*?)?$/i", $text, $match)) {
        if (vget('user/'.$userId.'/asked:nick_name')) {
          $response[] = "Saya tanya nama lho :)";
        }
        else {
          $response = array_merge($response, handleAnswer($userId, $match[1]));
        }
    }


    //MATH
    else if (!preg_match("/^\s*\(?\s*-?\d+(?:[,.]\d+)?\s*\)?\s*$/", $text) && (($result = evalMath($text, "(\D*?(be?ra?pa?( +sih|kah)?|hitung) *)?", "[^\d()]*\??")) || ($result === 0))) {
        $response[] = (string)$result;
        if (!preg_match("/\b(be?ra?pa?|hitung)\b/", $text)) {
          $response = array_merge($response, handleAnswer($userId, (string)$result));
        }
    }


    //MAKER
    else if (preg_match("/\b(y(an)?g +(mem)?(buat|bikin)( +(ini|".$kamu."|si?apa))?|(buat|bikin)an +si?apa)\b/i", $text)) {
        $response[] = "Kalau ada masalah dgn chat bot ini atau mau tanya-tanya soal matematika, hubungi aja @enof pake LINE";
    }



    else {  //unstructured or context based
        if (vget('user/'.$userId.'/asked:nick_name')) {
            if (preg_match("/^([a-z]{3,10})$/i", $text, $match)) {  //considered as a name
                if (preg_match("/[aiueo]/i", $text)) {  //must got vowel
                  $response = array_merge($response, acceptNickName($userId, $match[1]));
                }
                else {
                  $response[] = any([
                    "Itu kayanya bukan nama deh",
                    "Nama yg lebih gampang deh",
                  ]);
                }
            }
            else {
                $response[] = any([
                  "?",
                  "Nama panggilannya satu kata aja",
                ]);
            }
        }
        else if (vget('user/'.$userId.'/asked:place')) {
            if (preg_match("/^di +(([a-z]{3,10})( +([a-z]{3,10}))?)$/i", $text, $match)) {  //considered as a place
                $place = ucfirst(strtolower($match[1]));
                vset('user/'.$userId.'/place', $place);
                vdel('user/'.$userId.'/asked:place');
                $response[] = "Oh di " . $place;
            }
            else {
                $response[] = "?";
            }
        }
        else {
          $mods = modulesMatchTag($text);
          if (count($mods) > 0) {  //any recognizable keywords from question tags?
            $set = setLangLevelTags($userId, 'id', $text);
            $response[] = pickQuestion($userId, $set['level']); //override level if any
          }
        }
    }


    $firstContactTime = vget('user/'.$userId.'/first_contact_time');
    if (!$firstContactTime) {
        vset('user/'.$userId.'/first_contact_time', time());
    }
    vset('user/'.$userId.'/last_contact_time', time());
    vset('user/'.$userId.'/last_message', $oriText);

    addOtherResponses($response, $userId);

    return $response;
}

function addOtherResponses(&$response, $userId) {
    $nickName = vget('user/'.$userId.'/nick_name');
    if (!$nickName) { //not knowing the nick name
        if (!recently('user/'.$userId.'/last_contact_time', 30) && rand(0,2) == 0) {
          $response[] = [askNickName($userId), 1];
        }
    }
    else {
        if (!recently('user/'.$userId.'/last_contact_time', 60) && rand(0,8) == 0) {
            $response[] = ["Hai " . $nickName, 1];
            vset('user/'.$userId.'/asked:greeting', true);
        }
    }

    $msgcount = 0;
    foreach ($response as $r) {
      if ((is_string($r) || is_numeric($r)) && ($r || $r === 0 || $r === '0')) {
        $msgcount++;
      }
      else if (is_array($r) && count($r) > 0 && (is_string($r[0]) || is_numeric($r[0])) && ($r[0] || $r[0]===0 || $r[0] === '0')) {
        $msgcount++;
      }
    }

    if (rand(0, 5) == 0) {
      global $LINE;
      if (!vget('user/'.$userId.'/display_name')) { //no display name
        $info = $LINE->getProfile($userId);
        if ($info['displayName']) {
          vset('user/'.$userId.'/display_name', $info['displayName']);
        }
        if ($info['pictureUrl']) {
          vset('user/'.$userId.'/picture_url', $info['pictureUrl']);
        }
      }
    }

    if ($msgcount == 0) {  //no reply to user
      $count = vget('user/'.$userId.'/unrecognized_msg_count');
      if ($count == 0) {
        $response[] = any([
          "Maaf, saya ngga ngerti",
          "Maaf, ngga semua kata-kata bisa saya ngerti",
          "Saya ngga tau harus bilang apa",
          "Saya ngga tau harus jawab apa",
          "?",
          ":)",
        ]);
      }
      else if ($count == 1 || $count == 2) {
        $response[] = any([
          "Itu juga saya ngga ngerti",
          "Masih ngga ngerti",
          "Maaf, pemahaman saya masih terbatas",
          "?",
          ":)",
        ]);
      }
      else if ($count == 3) {
        $response[] = helpMenu();
        vset('user/'.$userId.'/last_help_time', time());
      }
      else {
        if (rand(0,4) == 0) {
          $response[] = "?";
        }
        else if (rand(0,1) == 0) {
          $response[] = "Kalau ada masalah dgn chat bot ini atau mau tanya-tanya soal matematika, hubungi aja @enof pake LINE";
        }
      }
      vinc('user/'.$userId.'/unrecognized_msg_count');
    }
    else {
      vdel('user/'.$userId.'/unrecognized_msg_count');
    }
}

function acceptNickName($userId, $nick) {
    $response = [];
    $nick = ucfirst(strtolower($nick));
    vset('user/'.$userId.'/nick_name', $nick);
    vdel('user/'.$userId.'/asked:nick_name');
    $response[] = any([
      "Ok " . $nick,
      "Ooh, " . $nick,
      "Baik ".$nick,
    ]);

    $response[] = [offerQuestion($userId), 1];
    return $response;
}

function recently($path, $time = 30) {  //in seconds
   return (time() - vget($path) < $time);
}

function listUser() {
    $arr = glob("userdata/user.*.txt");
    for ($i = 0; $i < sizeof($arr); $i++) {
      $arr[$i] = preg_replace("/^.*?user\.(U[a-f0-9]+)\..*/", "\\1", $arr[$i]);
      $nick = vget('user/' .$arr[$i] . '/nick_name');
      $name = vget('user/' .$arr[$i] . '/display_name');
      $time = vget('user/' .$arr[$i] . '/last_contact_time');
      $arr[$i] = substr($arr[$i], 0, 5) . ($nick ? '=' . $nick : '') . ($name ? '(' . $name . ')': '');
      $arr[$i] .= '['.date("j M H:i", $time+TZ).']';
    }
    return "Ada " . sizeof($arr). ":\n".implode("\n", $arr);
}


function sendUser($partialId, $msg) {
  global $LINE;
  $partialId = ucfirst($partialId);
  $arr = glob('userdata/user.'.$partialId.'*.txt');
  if (sizeof($arr) == 0) {
    return 'Ngga ada user itu';
  }
  else if (sizeof($arr) == 1) {
    $userId = preg_replace("/^.*?user\.(U[a-f0-9]+)\..*/", "$1", $arr[0]);
    $LINE->sendResponses([$msg], $userId);
    return 'Ok';
  }
  else {
    for ($i = 0; $i < sizeof($arr); $i++) {
      $arr[$i] = preg_replace("/^.*?user\.(U[a-f0-9]+)\..*$/", "$1", $arr[$i]);
      $arr[$i] = substr($arr[$i], 5, 5);
    }
    return "Yg mana? ".implode(", ", $arr);
  }
}


function getUserInfo($partialId) {
  global $LINE;
  $response = [];
  $partialId = ucfirst($partialId);
  $arr = glob('userdata/user.'.$partialId.'*.txt');
  if (sizeof($arr) == 0) {
    $reponse[] = 'Ngga ada user itu';
  }
  else if (sizeof($arr) == 1) {
    $userId = preg_replace("/^.*?user\.(U[a-f0-9]+)\..*/", "$1", $arr[0]);
    $info = $LINE->getProfile($userId);
    $reponse[] = implode("\n", [$info['displayName'], $info['pictureUrl']]);
  }
  else {  //ambigu
    for ($i = 0; $i < sizeof($arr); $i++) {
      $arr[$i] = preg_replace("/^.*?user\.(U[a-f0-9]+)\..*$/", "$1", $arr[$i]);
      $arr[$i] = substr($arr[$i], 5, 5);
    }
    $reponse[] = "Yg mana? ".implode(", ", $arr);
  }
  return $reponse;
}

function setLangLevelTags($userId, $lang, $text) {
    $level = false;
    $tags = [];
    if ($lang == "id") {
        //set language
        vset('user/'.$userId.'/lang', $lang);

        //set level
        if (preg_match("/\b(mudah|gampang)\b/i", $text)) {
          $text = preg_replace("/\b(mudah|gampang)\b/i", "", $text);
          $level = 0;
        }
        else if (preg_match("/\b(sedang|medium|menengah)\b/i", $text)) {
          $text = preg_replace("/\b(sedang|medium|menengah)\b/i", "", $text);
          $level = 1;
        }
        else if (preg_match("/\b(susah|sulit)\b/i", $text)) {
          $text = preg_replace("/\b(susah|sulit)\b/i", "", $text);
          $level = 2;
        }
    }
    else if ($lang == 'en') {
        //set language
        vset('user/'.$userId.'/lang', $lang);

        //set level
        if (preg_match("/\b(easy|easier)\b/i", $text)) {
          $text = preg_replace("/\b(easy|easier)\b/i", "", $text);
          $level = 0;
        }
        else if (preg_match("/\b(medium)\b/i", $text)) {
          $text = preg_replace("/\b(medium)\b/i", "", $text);
          $level = 1;
        }
        else if (preg_match("/\b(hard|harder)\b/i", $text)) {
          $text = preg_replace("/\b(hard|harder)\b/i", "", $text);
          $level = 2;
        }
    }

    if ($level !== false) {
      vset('user/'.$userId.'/default_level', $level);
    }

    $tags = implode(",", preg_split("/\W+/", strtolower(trim($text))));
    vset('user/'.$userId.'/default_tags', $tags);

    return ['lang'=>$lang, 'level'=>$level, 'tags'=>$tags];
}


function modulesMatchTag($text, $lang = 'id') {
    $langMods = explode(",", vget("system/0/question_lang/" . $lang));

    $tags = implode(",", preg_split("/\W+/", strtolower(trim($text))));
    if (!$tags) $tags = '';
    $tags = explode(",", $tags);

    $mods = [];
    foreach ($tags as $tag) {
      $list = vget("system/0/question_tag/" . $tag);
      if ($list) {
        $tagMods = explode(",", $list);
        $mods = array_merge($mods, array_intersect($langMods, $tagMods));
      }
    }

    return $mods;
}

function pickQuestion($userId, $level = false, $module = false) {
    $lang = vget('user/'.$userId.'/lang');
    if (!$lang) $lang = 'id';

    if ($module === true) { //use same module as last
      $params = json_decode(vget("user/".$userId."/current_question_context"), true);
      $module = $params['module'];
    }

    if (!$module) {
      $tags = vget('user/'.$userId.'/default_tags');
      $mods = modulesMatchTag($tags, $lang);

      if (sizeof($mods) == 0) { //no matching tags found
    	$langMods = explode(",", vget("system/0/question_lang/" . $lang));
        $mods = $langMods;  //fallback to full list
      }

      $module = $mods[rand(0, sizeof($mods) - 1)];
    }

    if ($level === false) {  //use achieved level for this module
      $level = vget('user/'.$userId.'/level_question/'.$module);
    }
    else if ($level === true) { //use same as last level
      $params = json_decode(vget("user/".$userId."/current_question_context"), true);
      $level = $params['level'];
    }

    if ($level === false) { //fallback
      $level = vget('user/'.$userId.'/default_level');
    }

    $context = [
      'lang' => $lang,
      'level' => $level,
    ];

    include_once('question/' . $module . '.class.php');
    $q = new $module($context);
    $q->params['module'] = $module;
    $q->params['level'] = $level;
    $q->params['topic'] = ($module::info())['topic'];


    vset("user/".$userId."/current_question_context", json_encode($q->params));
    vset('user/'.$userId.'/current_question_time', time());
    vdel('user/'.$userId.'/count_try');
    vdel('user/'.$userId.'/count_guess');
    vdel('user/'.$userId.'/last_try_time');
    vdel('user/'.$userId.'/last_hint_time');

    return askQuestion($userId);
}

function askNickName($userId) {
    if (!vget("user/".$userId."/nick_name") && !vget("user/".$userId."/asked:nick_name")) {
      vdel('user/'.$userId.'/asked:*');
      vset('user/'.$userId.'/asked:nick_name', true);

      return "Nama kamu siapa?";
    }
}

function offerQuestion($userId) {
    if (!vget("user/".$userId."/asked:question_answer")) {
      vdel("user/".$userId."/asked:*");
      vset("user/".$userId."/asked:want_question", true);

      return "Mau latihan soal matematika?";
    }
}

function helpMenu() {
    $samples = [
      "ada topik apa aja?",
      "minta soal",
      "soal aritmatika",
      "soal tentang aljabar yang susah",
      "rumusnya apa?",
      "berapa skor saya?",
      "usul topik perkalian",
      "berapa 1+1?",
    ];
    for ($i=0; $i<count($samples); $i++) {
      $samples[$i] = '"' . $samples[$i] . '"';
    }
    return any([
      "Yang bisa kamu katakan di sini misalnya",
      "Yang bisa saya mengerti misalnya",
    ])."\n".implode(",\n", $samples);
}

function askQuestion($userId) {
    $params = json_decode(vget("user/".$userId."/current_question_context"), true);
    vdel('user/'.$userId.'/asked:*');
    vset("user/".$userId."/asked:question_answer", true);
    return str_repeat('★', $params['level'] + 1) . " " . $params['sentence'];
}

function handleCorrectAnswer($userId, $text, $params) {
    $response = [];
    if (vget('user/'.$userId.'/count_try') == 0) {
      $emoji = '(' . any(['amazed', 'yeah', 'yes', 'clap']) . ')';
      $nick = vget('user/' .$userId . '/nick_name');
      $nick =  ($nick ? ' ' . $nick : '');
      if ($params['lang'] == 'en') {
        $str = $emoji . ' ' . any(['Bravo!', 'Right!', "Yes! Correct"]);
      }
      else {
        $str = $emoji . ' ' . any(['Hebat'.$nick.'!', 'Betul'.$nick.'!', "Ya! Betul".$nick]);
      }
      if (rand(0,1) == 0) {
        $str .= ", " . ($params['lang'] == 'en' ? 'good job' : 'langsung terjawab');
      }
      $response[] = $str;
    }
    else if (vget('user/'.$userId.'/count_try') > 3) {
      if ($params['lang'] == 'en') {
        $str = 'Correct! you finally got it right';
        $response[] = $str;
      }
      else {
        $str = any([
          'Betul! akhirnya dapet juga jawabannya',
        ]);
        $response[] = $str;
      }
    }
    else {
      $response[] = ($params['lang'] == 'en' ? 'Correct! (smile)' : 'Betul! (smile)');
    }

    if (!$params['level']) $params['level'] = 0;
    vinc('user/'.$userId.'/count_answered_correct/level_'.$params['level'].'/'.$params['module']);

    //is it time to auto increase level?
    $currLevel = vget('user/'.$userId.'/level_question/'.$params['module']);
    if (!$currLevel) $currLevel = 0;
    if ($currLevel <= 2) {
      $count = vget('user/'.$userId.'/count_answered_correct/level_'.$currLevel.'/'.$params['module']);
      if ($count >= 5) {
        //increase the default level for this particular type of question
        vset('user/'.$userId.'/level_question/'.$params['module'], $currLevel + 1);
        $response[] = any([
          "Selamat! berikutnya kalau dapat soal tentang ". $params['topic'] . " lagi siap-siap yang agak susah yah? :)",
        ]);
      }
      else {
        //$response[] = "Butuh ".(5 - $count) . " kali lagi menjawab soal dgn topik sejenis, biar naik level";
      }
    }
    vinc('user/'.$userId.'/count_answered_correct/level_'.$currLevel.'/'.$params['module']);  //per module
    vinc('user/'.$userId.'/count_answered_correct');  //total

    if (vget('user/'.$userId.'/promo_beta_apr2017')) {
      if (vget('user/'.$userId.'/count_answered_correct') == 49) {
        if (date("Y-m-d") <= "2017-04-06") {
          if (vget('system/0/promo_beta_apr2017/winner_count') < 10) {
            $response[] = "Terima kasih! Kamu sudah menjawab 25 soal dan mendapat hadiah pulsa sebesar Rp 25.000\n".
                          "Pulsanya mau diisikan ke nomor hape berapa?";
            vdel('user/'.$userId.'/promo_beta_apr2017');
            vset('user/'.$userId.'/winner_promo_beta_apr2017', time());
            vset('user/'.$userId.'/important_asked:phone_number', true);
            vinc('system/0/promo_beta_apr2017/winner_count');
            notifyAdmin("User menang: ". substr($userId, 0, 5));
            return $response;
          }
          else {
            $response[] = "Terima kasih, kamu sudah menjawab 25 soal, tapi bukan termasuk 10 orang pertama";
          }
        }
        else {
          $response[] = "Terima kasih, kamu sudah menjawab 25 soal, tapi sudah lewat dari batas waktu promo";
        }
      }
    }


    $nick = vget('user/' .$userId . '/nick_name');
    if (!$nick) {
      $response[] = askNickName($userId);
    }
    else {
      if (rand(0,2) == 0) {
        $response[] = 'Kamu bisa ketik "skor" untuk tau hasilmu sampai saat ini';
      }
      $response[] = [any(["Siap utk soal berikutnya...", "Soal berikutnya...", "Selanjutnya...", "Soal selanjutnya...", "Siap lagi..."]), 2];
      $response[] = [pickQuestion($userId), 3]; //new module and its achieved level
    }
    return $response;
}


function handleWrongAnswer($userId, $text, $params, $solution) {
    $response = [];

    if ((strpos($solution, "/") !== false) && (strpos($text, "/") === false)) { //solution requires fraction, but user may not know
      if ($params['lang'] == 'en') {
        $response[] = 'The answer should be in fraction, example: '.mt_rand(1, 4).'/'.mt_rand(5, 9);
        $response[] = 'Try again';
      }
      else {
        $response[] = 'Jawabannya dalam bentuk pecahan, contoh: '.mt_rand(1, 4).'/'.mt_rand(5, 9);
        $response[] = 'Coba lagi';
      }
    }
    else if ((strpos($solution, ".") !== false) && (strpos($text, ".") === false)) { //solution requires decimal, but user may not know
      if ($params['lang'] == 'en') {
        $response[] = 'The answer should be in decimal, example: '.mt_rand(3, 9).'.'.mt_rand(11, 99);
        $response[] = 'Try again';
      }
      else {
        $response[] = 'Jawabannya dalam bentuk desimal, contoh: '.mt_rand(3, 9).','.mt_rand(11, 99);
        $response[] = 'Coba lagi';
      }
    }
    else if (((strpos($solution, ".") === false) && (strpos($text, ".") !== false))
           ||((strpos($solution, "/") === false) && (strpos($text, "/") !== false))) { //solution requires whole number, but user may not know
      if ($params['lang'] == 'en') {
        $response[] = 'The answer should be in whole number, example: '.mt_rand(3, 20);
        $response[] = 'Try again';
      }
      else {
        $response[] = 'Jawabannya dalam bilangan bulat, contoh: '.mt_rand(3, 20);
        $response[] = 'Coba lagi';
      }
    }
    else {
      $countGuess = vget('user/'.$userId.'/count_guess');
      if ($countGuess > 0 && $countGuess % rand(3,4) == 0) {
        $emoji = any(["(no)"]);
        if ($params['lang'] == 'en') {
          $response[] = $emoji. " Are you just guessing?";
        }
        else {
          $response[] = $emoji. ' Tebak-tebakan aja ya?';
        }

        if (rand(0,3) == 0) {
          $response[] = [($params['lang'] == 'en' ? "Try to think before answering" : 'Coba dihitung dulu sebelum jawab'), 1];
        }
      }
      else if ($countGuess > 10) {
        $response[] = 'Bilang \"nyerah\" aja deh';
      }
      else {
        $countTry = vget('user/'.$userId.'/count_try');

        if ($params['lang'] == 'en') {
          $response[] = any([
            "No",
            "Not that",
            "No, try again",
            "Try again",
          ]);
        }
        else {
          $response[] = any([
            "Bukan",
            "Bukan itu",
            "Bukan, coba lagi",
            "Coba lagi",
            "Masih belum betul",
          ]);
        }

        if ($countTry == 4 || ($countTry > 4 && rand(0,2) == 0)) {
          $response[] = ['Kamu bisa minta "ganti soal" atau "nyerah" kalau yang ini susah', 1];
          $currLevel = vget('user/'.$userId.'/level_question/'.$params['module']);
          if ($currLevel > 0) {
            $response[] = ['Atau bisa juga minta "yang lebih mudah"', 1];
          }
        }
      }

      vinc('user/'.$userId.'/count_try');

      $lastTry = vget('user/'.$userId.'/last_try_time');
      if (time() - $lastTry < 5) {
        vinc('user/'.$userId.'/count_guess');
      }
      else {
        vdel('user/'.$userId.'/count_guess');
      }
      vset('user/'.$userId.'/last_try_time', time());

    }

    return $response;
}

function handleAnswer($userId, $text) {
    $text = strtolower(trim($text));
    $response = [];
    $params = json_decode(vget("user/".$userId."/current_question_context"), true);
    if (!vget('user/'.$userId.'/asked:question_answer') || !isset($params['sentence'])) { //no current question is active
      return [ offerQuestion($userId) ];
    }
    $solution = $params[ $params['unknown'][0] ];

    $text = preg_replace("/,/", ".", $text);  //normalize comma into dot (in case indonesian)
    $solution = preg_replace("/,/", ".", $solution);  //normalize comma into dot (in case indonesian)

    if ($text == $solution) {
        $response = array_merge($response, handleCorrectAnswer($userId, $text, $params));
    }
    else {
        $response = array_merge($response, handleWrongAnswer($userId, $text, $params, $solution));
    }
    return $response;
}

function changeLevel($userId, $direction) {
    $response = [];
    $params = json_decode(vget("user/".$userId."/current_question_context"), true);
    if (@$params['module']) {
      $currLevel = (int) vget('user/'.$userId.'/level_question/'.$params['module']);

      $change = 0;
      $levelName = '';
      if ($direction == 'up') {
        $levelName = 'susah';
        if ($currLevel < 2) {
          $change = 1;
        }
      }
      else if ($direction == 'down') {
        $levelName = 'mudah';
        if ($currLevel > 0) {
          $change = -1;
        }
      }

      if ($change != 0) {
        //change the default level for this particular type of question
        $reponse[] = "Kalau gitu, sekarang yg lebih ".$levelName." yah";
        $currLevel += $change;
        vset('user/'.$userId.'/level_question/'.$params['module'], $currLevel);
    
        $response[] = any([
          "Ok, sekarang yg agak ",
          "Ok, sekarang yg lebih ",
        ]).$levelName;
        $response[] = [pickQuestion($userId, $currLevel, true), 1]; //override level, and keep same module
      }
      else {
        $response[] = any([
          "Itu udah yang ".$levelName,
        ]);
      }
    }
    return $response;
}


function handleGiveUp($userId) {
    if (!vget('user/'.$userId.'/asked:question_answer')) {
      return [];
    }
    if (vget('user/'.$userId.'/count_try') == 0) {
      $response[] = any([
        "Coba dulu dijawab",
        "Coba kamu jawab dulu",
        "Belum juga dicoba jawab :)"
      ]);
    }
    else if ((time() - vget('user/'.$userId.'/current_question_time') > 2 * 60) //more than 2 minutes
        || (vget('user/'.$userId.'/count_try') >= 3)) {

      $params = json_decode(vget("user/".$userId."/current_question_context"), true);
      $solution = $params[ $params['unknown'][0] ];
      $solution = preg_replace("/\./", ",", $solution); //change dot into comma for indonesian

      $response[] = any([
        "Jawabannya " . $solution,
        "Nih, jawabannya " . $solution,
        "Jawabannya adalah " . $solution,
        "Yang betul adalah " . $solution,
      ]);

      vdel('user/'.$userId.'/count_try');
      vdel('user/'.$userId.'/count_guess');
      vdel('user/'.$userId.'/last_try_time');

      $response[] = [any(["Siap buat soal berikutnya...", "Soal berikutnya...", "Selanjutnya...", "Soal selanjutnya...", "Siap lagi..."]), 1];
      $response[] = [pickQuestion($userId), 3]; //new module and its achieved level
    }
    else {
      $response[] = any([
        "Jangan nyerah, coba dulu",
        "Dicoba cari jawabannya",
      ]);
      if (rand(0, 3) == 0) {
        $response[] = ["Kalau ngga bisa juga, nanti dikasih tau", 1];
      }
    }
    return $response;
}

function infoScore($userId, $detail=false) {
    $score = vget('user/'.$userId.'/count_answered_correct');
    if ($score == 0) {
      return any([
        "Masih kosong",
        "Skor kamu masih kosong",
        "Kamu belum dapat skor",
        "Kamu belum menjawab soal satu pun",
      ]);
    }
    else if (!$detail) {  //just the total
      $str = any([
        "Kamu udah berhasil jawab ".$score." soal",
        "Kamu udah berhasil menjawab ".$score." soal",
        "Kamu udah menjawab ".$score." soal",
        "Skor kamu ".$score,
        $score,
      ]);

      if (rand(0, 1) == 0 || !vget('user/'.$userId.'/know_detail_score')) {
        $str .= "\n". 'Kamu juga bisa minta "rincian skor" kalau mau';
        vset('user/'.$userId.'/know_detail_score', true);
      }
      return $str;
    }
    else {  //detail
      $result = [];
      $countPerlevel = [];
      $countPerModule = [];

      $result[] = "Total menjawab " . $score . " soal";

      $paths = vget('user/'.$userId.'/count_answered_correct/level_*');
      foreach ($paths as $path => $val) {
        list($tab, $id, $key, $level, $module) = explode("/", $path, 5);
        $level = (int) preg_replace("/\D/", "", $level);
        if (!$level) $level = 0;

        if (!isset($countPerLevel[$level])) {
          $countPerLevel[$level] = 0;
        }
        $countPerLevel[$level] += (int)$val;
        
        if (!isset($countPerModule[$module])) {
          $countPerModule[$module] = 0; //init
        }
        $countPerModule[$module] += (int)$val;
      }

      $str = '';
      for ($i = 0; $i <= 2 ; $i++) {
        $str .= ($str ? ', ' : '') . str_repeat('★', $i + 1) . (isset($countPerLevel[$i]) ? $countPerLevel[$i] : '0');
      }
      $result[] = $str;

      foreach ($countPerModule as $module=>$count) {
        if (!file_exists('question/'.$module.'.class.php')) continue;
        include_once('question/'.$module.'.class.php');
        $info = $module::info();
        $topic = $info['topic'];

        $result[] = $topic . ' = ' . $count;
      }

      return implode("\n", $result);
    }
}


function getOtherUsers($userId) {
    $arr = glob("userdata/user.*.txt");
    $nicks = [];
    for ($i = 0; $i < sizeof($arr); $i++) {
      $arr[$i] = preg_replace("/^.*?user\.(U[a-f0-9]+)\..*/", "\\1", $arr[$i]);
      if ($arr[$i] == $userId) {  //except myself
        continue;
      }
      $nick = vget('user/' .$arr[$i] . '/nick_name');
      if (!$nick) {
        $nick = vget('user/'.$arr[$i].'/display_name');
        if (!$nick) {
          $nick = substr($arr[$i], 0, 4);
        }
      }
      $nicks[ $arr[$i] ] = $nick;
    }
    return $nicks;
}

function handleChallengeMode($userId) {
    $others = getOtherUsers($userId);
    $online = [];
    foreach ($others as $user => $nick) {
      if (recently('user/'.$user.'/last_contact_time', 30)) { //considered online
        $online[] = $nick;
        if (vget('user/'.$user.'/ready_for_challenge')) {
          
        }
      }
    }

    if (count($online) > 0) {
      $response[] = implode(", ", $online);
    }
    else {
      $response[] = "Sedang ngga ada orang lain yg bisa diajak duel";
    }
    return $response;
}

function listModules($refresh = false) {
  if ($refresh && $d = opendir('question')) {
    $modules = [];
    $mlangs = [];
    $mtags = [];
    $mtopics = [];
    while ($f = readdir($d)) {
      if (!preg_match("/^(.+?)\.class\.php$/", $f, $match)) continue;
      include_once('question/'.$f);
      $class = $match[1];
      $info = $class::info();
      foreach ($info['tags'] as $lang => $tags) {
        if (!isset($mlangs[$lang])) $mlangs[$lang] = [];
        $mlangs[$lang][] = $class;
        foreach ($tags as $tag) {
          if (!isset($mtags[$tag])) $langs[$tag] = [];
          $mtags[$tag][] = $class;
        }

        if (!isset($mtopics[$lang])) $mtopics[$lang] = [];
        $mtopics[$lang][] = $info['topic'];
      }
      $modules[] = $class;
    }
    closedir($d);

    //return implode(",", $mtopics['id']);

    vdel("system/0/question_topics/*");
    vdel("system/0/question_modules");
    vdel("system/0/question_lang/*");
    vdel("system/0/question_tag/*");

    if (sizeof($modules) > 0) {
      vset("system/0/question_modules", implode(",", $modules));

      foreach ($mlangs as $lang => $mods) {
        vset("system/0/question_lang/" . $lang, implode(",", $mods));
      }
      foreach ($mtags as $tag => $mods) {
        vset("system/0/question_tag/" . $tag, implode(",", $mods));
      }
      foreach ($mtopics as $lang => $topics) {
        vset("system/0/question_topics/" . $lang, implode(",", $topics)); //human friendly
      }
    }
  }
  return ($refresh ? "Refresh:\n" : "") . implode(", ", explode(",", vget("system/0/question_modules")));
}
