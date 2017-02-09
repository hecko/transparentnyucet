<?php

echo("----------new sync----------\n");

require('lib/functions.php');
#require('db.php');
require('config.php');

//DataBanking SLSP zvladne naraz vyexportovat len 200 pohybov na ucte
$datum_od=date("j.m.Y",time()-(3600*24*10));
echo("Datum od: $datum_od\n");

//create a cookie file
$cfile = tempnam("/tmp", "CURLCOOKIE");

echo("-- Nasleduje inicializacia sessny\n");
print("Cookie temp file: " . $cfile . "\n");

//initialize a curl instance
$c = curl_init();
curl_setopt($c, CURLOPT_URL, "https://ib.slsp.sk/ebanking/ibxindex.xml");
//save the cookie here
curl_setopt($c, CURLOPT_COOKIEJAR, $cfile);
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
curl_setopt($c, CURLOPT_LOW_SPEED_LIMIT, 1);
curl_setopt($c, CURLOPT_LOW_SPEED_TIME, 30);
if (!$out = curl_exec($c)) {
    echo("Unable to make cURL request to init session!\n");
    echo(curl_error($c) . "\n");
    die();
}
echo("Returned data from HTTP request:\n");
echo($out);
print_r($out);

curl_setopt ($c, CURLOPT_COOKIEFILE, $cfile);
curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
$output = curl_exec ($c);

echo("-- Preparing to log in...\n");

$data = array('user_id' => $ib_cli,'tap'=>'2','pwd' => $ib_pass,'lng2'=>'en');
$post = http_build_query($data, '', '&');
curl_setopt($c, CURLOPT_URL, "https://ib.slsp.sk/ebanking/login/ibxlogin.xml");
curl_setopt ($c, CURLOPT_COOKIEFILE, $cfile);
curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
curl_setopt($c, CURLOPT_POST, true);
curl_setopt($c, CURLOPT_POSTFIELDS, $post);
$output = curl_exec ($c);

echo("-- Login output from CURL:\n");
print_r($output);

echo("-- Loading XML data...\n");
$xml = simplexml_load_string($output);
$name = $xml->result->{'reply-login'}->name." ".$xml->result->{'reply-login'}->surname;
echo("Nalogovany user: $name\n");

echo("-- Pokracujeme na zoznam uctov...\n");

curl_setopt($c, CURLOPT_URL, "https://ib.slsp.sk/ebanking/accounts/ibxaccounts.xml");
curl_setopt ($c, CURLOPT_COOKIEFILE, $cfile);
curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
$output = curl_exec ($c);
$xml = simplexml_load_string($output);

$i = 0;
foreach ($xml->result->{'reply-account-list'}->children() as $account) {
  $acc[$i]['account_uid'] = (string) $account->{'account-id'};
  $acc[$i]['account_utyp'] = (string) $account->{'account-type'};
  $acc[$i]['account_ucis'] = (string) $account->{'account-number'};
  $acc[$i]['account_uprcis'] = (string) $account->{'account-prefix'};
  $acc[$i]['account_iban'] = (string) $account->{'account-iban'};
  $acc[$i]['account_name'] = (string) $account->{'account-name'};
  $acc[$i]['account_name_www'] = (string) $account->{'account-name-www'};
  $acc[$i]['account_disp_balance_eur'] = (string) $account->{'disp_balance_eur'};
  $acc[$i]['account_currency'] = (string) $account->{'currency'};
  $acc[$i]['account_prefix'] = (string) $account->{'account-prefix'};
  $acc[$i]['account_number'] = (string) $account->{'account-number'};
  $i++;
}

echo('=== Ideme cyklovat - pre kazdy ucet vyberat tranzakcie');

foreach ($acc as $a) {

  $log->logInfo('-- Vlozenie info o ucte do databazy');
  $query = "INSERT INTO accounts (iban,name,disp_balance_eur,account_prefix,account_number,currency) VALUES ('"
    .mysql_real_escape_string($a['account_iban'])."','"
    .mysql_real_escape_string($a['account_name'])." (".mysql_real_escape_string($a['account_name_www']).")','"
    .mysql_real_escape_string($a['account_disp_balance_eur'])."','"
    .mysql_real_escape_string($a['account_prefix'])."','"
    .mysql_real_escape_string($a['account_number'])."','"
    .mysql_real_escape_string($a['account_currency'])."'"
    .") ON DUPLICATE KEY UPDATE name='"
    .mysql_real_escape_string($a['account_name'])." (".mysql_real_escape_string($a['account_name_www']).")',"
    ." disp_balance_eur='".mysql_real_escape_string($a['account_disp_balance_eur'])."',"
    ." account_prefix='".mysql_real_escape_string($a['account_prefix'])."',"
    ." account_number='".mysql_real_escape_string($a['account_number'])."',"
    ." currency='".mysql_real_escape_string($a['account_currency'])."'";

  mysql_query($query);

  $log->logInfo("-- Nasleduje vyber uctu...");
  $log->logInfo("Ucet: ".$a['account_uid']." - IBAN:".$a['account_iban']);

  $data = array('uid' => $a['account_uid'],'utyp' => $a['account_utyp'],'ucis' => $a['account_ucis'],'uprcis' => $a['account_uprcis']);
  $post = http_build_query($data, '', '&');
  curl_setopt($c, CURLOPT_URL, "https://ib.slsp.sk/ebanking/accinfo/ibxaccinfo.xml");
  curl_setopt ($c, CURLOPT_COOKIEFILE, $cfile);
  curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_POSTFIELDS, $post);
  $output = curl_exec ($c);

  if (preg_match("<ok/>",$output)) {
    $log->logInfo('OK');
  } else {
    $log->logInfo('FAIL');
    die;
  }

  $log->logInfo('-- Vylistovanie obratov...');

  $data = array(
    'no_f_no_od' => $datum_od, //datum od
    'no_s_how_much' => 'showall', //vsetky zaznamy naraz
    'no_s_amounts' => 'amntnone',
  );
  $post = http_build_query($data, '', '&');
  curl_setopt($c, CURLOPT_URL, "https://ib.slsp.sk/ebanking/accto/ibxtofilter.xml");
  curl_setopt ($c, CURLOPT_COOKIEFILE, $cfile);
  curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_POSTFIELDS, $post);
  $output = curl_exec ($c);

  if (preg_match("/<error>/",$output)) {
    $log->logInfo(nl2br($output));
    slsp_logout($c,$cfile,$log);
    die;
  }

  $xml = simplexml_load_string($output);

  $i = 0;
  foreach ($xml->result->turnovers->children() as $t) { //$t - turnover
    $turnovers[$i]['transid'] = (string) $t->attributes()->transid; 
    $turnovers[$i]['amount'] = (string) $t->{'amount'};
    $turnovers[$i]['note'] = (string) $t->{'note'};
    $turnovers[$i]['description'] = (string) $t->{'description'};
    $turnovers[$i]['constant-symb'] = (string) $t->{'constant-symb'};
    $turnovers[$i]['currency'] = (string) $t->{'currency'};
    $turnovers[$i]['maturity-date'] = (string) date("Y-m-d",strtotime($t->{'maturity-date'}));
    $i++;
  }

  $log->logInfo('Number of all turnovers: '.count($turnovers));

  $new_turnovers=0;
  foreach ($turnovers as $t) {
    $query = "INSERT INTO turnovers (iban,transid,amount,note,description,maturitydate,currency,constantsymb) VALUES ('".
      mysql_real_escape_string($a['account_iban'])."','".
      mysql_real_escape_string($t['transid'])."','".
      mysql_real_escape_string($t['amount'])."','".
      mysql_real_escape_string($t['note'])."','".
      mysql_real_escape_string($t['description'])."','".
      mysql_real_escape_string($t['maturity-date'])."','".
      mysql_real_escape_string($t['currency'])."','".
      mysql_real_escape_string($t['constant-symb'])."')";
    if (mysql_query($query)) {
      $new_turnovers++;
    } else {
    };
  }
  $log->logInfo('Number of new turnovers: '.$new_turnovers);

  unset($turnovers,$new_turnovers);

}
$log->logInfo('=== Koniec cyklu zbierania dat z uctov.');

slsp_logout($c,$cfile,$log);

//vymazanie zaznamov z databazy ktore su starsie ako 5 dni

$query="DELETE FROM turnovers WHERE maturitydate<'".date("Y-m-d",time()-(3600*24*5))."'"; 
$log->logInfo('Database cleanup... '.$query);
if (!mysql_query($query)) {
  $log->logError('Deleting old information fro DB failed.');
} else {
  $log->logInfo('Deleted information from database (number removed): '.mysql_affected_rows());
}

$log->logInfo('-- End of synchronization');
?>
