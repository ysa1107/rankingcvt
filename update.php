<?php
  define('AUTHOR', 'T-Rekt');
  define('COPYRIGHT', 'J2TEAM');

  define('secret','i_am_developer_ysa');
  define('cookie', 'm_pixel_ratio=1; sb=-pvyWvp82d7Gylsom9Xeywje; datr=-5vyWos-1uZ3qqMqk2HXXxSc; x-referer=eyJyIjoiL3NldHRpbmdzL2Fkcy8iLCJoIjoiL3NldHRpbmdzL2Fkcy8iLCJzIjoibSJ9; locale=en_US; pl=n; c_user=1070119029; xs=49%3A_6ibbGcYmxCAsA%3A2%3A1529659528%3A17170%3A6165; spin=r.4036997_b.trunk_t.1529659529_s.1_v.2_; js_ver=3094; fr=0mH5t6n8ET0LwrlMR.AWWyPtvkMcLWUr7JqNZ-AeA_jYg.Ba8ppT.co.Fss.0.0.BbLM6w.AWWe0FCI; wd=1547x408; act=1529663480850%2F1; presence=EDvF3EtimeF1529663493EuserFA21070119029A2EstateFDutF1529663493952CEchFDp_5f1070119029F3CC');
  define('gid','715871168601819');
?>

<?php
  $GLOBALS['ONE_DAY'] = 60*60*24;
  $GLOBALS['DOC_IDS'] = [
    "engagement" => "1470044149684839",
    "member" => "1554851827859432",
    "growth" => "1761498670534891",
    "highlights" => "1378499845591554"
  ];

  function request($url = '', $headers = [] , $params = [], $post = 0)    {
    $c = curl_init();
    $opts = [
      CURLOPT_URL => $url.(!$post && $params ? '?'.http_build_query($params) : ''),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER =>  $headers,
      CURLOPT_SSL_VERIFYPEER => false
    ];
    if($post){
      $opts[CURLOPT_POST] = true;
      $opts[CURLOPT_POSTFIELDS] = $params;
    }
    curl_setopt_array($c, $opts);
    $d = curl_exec($c);
    curl_close($c);
    return $d;
  }

  function getFbDtsg($headers) {
    $html = request("https://www.facebook.com/", $headers);
    $fb_dtsg = preg_match('/DTSGInitialData.+?:"(.+?)"/', $html, $matches);
    return $fb_dtsg?$matches[1]:0;
  }

  function makeQuery($start_time, $end_time) {
    return json_encode([
      "groupID"=> gid,
      "startTime"=> $start_time,
      "endTime"=> $end_time,
      "ref"=> null
    ]);
  }

  function getData($doc_id, $start_time, $end_time, $fb_dtsg, $headers) {
    $post_data = http_build_query([
      "__a" => "1",
      "fb_dtsg" => $fb_dtsg,
      "variables" => makeQuery($start_time, $end_time),
      "doc_id" => $doc_id
    ]);

    return request("https://www.facebook.com/api/graphql/", $headers, $post_data, 1);
  }

  function getGroupInfo($headers) {
    $html = request("https://www.facebook.com/groups/".gid, $headers);
    $group_name = preg_match('/<title id="pageTitle">(.+?)<\/title>/', $html, $matches);
    $pending_posts = preg_match('/\/pending\/">([0-9]+)/', $html, $matches1);
    return [
      "group_name" => $group_name?$matches[1]:0,
      "pending_posts" => $pending_posts?$matches1[1]:0
    ];
  }

  function doAll() {
    try {
      $headers = [
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36",
        "Content-Type: application/x-www-form-urlencoded",
        "Cookie: ".cookie
      ];
      $fb_dtsg = getFbDtsg($headers);
      if (!$fb_dtsg || strpos($fb_dtsg, ":")===False) return 0;
      $full = [];
      $info = getGroupInfo($headers);
      $group_name = $info['group_name'];
      $pending_posts = $info['pending_posts'];
      if (!$group_name) return 0;
      $full['group_name'] = $group_name;
      $full['pending_posts'] = $pending_posts;
      foreach ($GLOBALS['DOC_IDS'] as $doc_name => $doc_id) {
        $data = getData($doc_id, time()-$GLOBALS['ONE_DAY']*30, time(), $fb_dtsg, $headers);
        $full[$doc_name] = json_decode($data,1)['data']['group']['group_insights'];
      }
      $full['last_update'] = time();
      $f = fopen("full.json", "w");
      fwrite($f, json_encode($full));
      fclose($f);
      return 1;
    }
    catch (Exception $e) {
      return 0;
    }
  }

  if (isset($_GET['secret'])) {
    $secret = $_GET['secret'];
    if ($secret!==secret) {
      echo 0;
      return;
    }
    echo doAll();
  }
  else echo("Wrong secret");
