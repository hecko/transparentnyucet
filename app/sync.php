<?php

echo("----------new sync----------\n");

require('lib/functions.php');
#require('db.php');
require('config.php');
include('config_local.php');

$month = $argv[1];

// DataBanking SLSP zvladne naraz vyexportovat len 200 pohybov na ucte
$datum_od = date("1." . $month . ".Y",time());

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
echo("\n");

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
echo("\n");

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

echo("=== Ideme cyklovat - pre kazdy ucet vyberat tranzakcie\n");

foreach ($acc as $a) {

  echo("-- Info o ucte\n");
  print_r($a);
  echo("\n");

  echo("-- Nasleduje vyber uctu...\n");
  echo("Ucet: ".$a['account_uid']." - IBAN: ".$a['account_iban'] . "\n");

  $data = array('uid' => $a['account_uid'],'utyp' => $a['account_utyp'],'ucis' => $a['account_ucis'],'uprcis' => $a['account_uprcis']);
  $post = http_build_query($data, '', '&');
  curl_setopt($c, CURLOPT_URL, "https://ib.slsp.sk/ebanking/accinfo/ibxaccinfo.xml");
  curl_setopt ($c, CURLOPT_COOKIEFILE, $cfile);
  curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_POSTFIELDS, $post);
  $output = curl_exec ($c);

  if (preg_match("<ok/>",$output)) {
    echo("OK\n");
  } else {
    echo("FAIL\n");
    die;
  }

  echo("-- Vylistovanie obratov...\n");

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
    $turnovers[$i]['counter-name'] = (string) $t->{'counter-name'};
    $turnovers[$i]['counter-bank'] = (string) $t->{'counter-bank'};
    $turnovers[$i]['counter-account'] = (string) $t->{'counter-account'};
    $turnovers[$i]['message1'] = (string) $t->{'message1'};
    $turnovers[$i]['message2'] = (string) $t->{'message2'};
    $turnovers[$i]['variable-symb'] = (string) $t->{'variable-symb'};
    $turnovers[$i]['maturity-date'] = (string) date("Y-m-d",strtotime($t->{'maturity-date'}));
    $i++;
  }

  echo('Number of all turnovers: ' . count($turnovers) . "\n");

  foreach ($turnovers as $t) {
    # print_r($t);
    # echo("\n");
    echo($t['maturity-date'] . "   " . $t['amount'] . " " . $t['currency'] . "\n");
    echo("   Var symbol: " . $t['variable-symb'] . "\n");
    echo("   Poznamka:   " . $t['note'] . "\n");
    echo("   Popis:      " . $t['description'] . "\n");
    echo("   Protiucet:  " . $t['counter-name'] . "   (" . $t['counter-account'] . "/" . $t['counter-bank'] . ")\n");
    echo("   Zaznam 1:   " . $t['message1'] . "\n");
    echo("   Zaznam 2:   " . $t['message2'] . "\n");
    echo("\n");
  }

  unset($turnovers);

}
echo("=== Koniec cyklu zbierania dat z uctov.\n");

slsp_logout($c,$cfile);

//vymazanie zaznamov z databazy ktore su starsie ako 5 dni

echo("-- End of synchronization\n");
?>
