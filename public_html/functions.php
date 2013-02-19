<?php

function last_sync() {
  $query = "SELECT * FROM sync_runs ORDER BY timestamp DESC LIMIT 1";
  $raw = mysql_query($query);
  $r = mysql_fetch_assoc($raw);
  return date("j. M Y \o H:i",time());
  return date("j. M Y \o H:i",$r['timestamp']);
}

function last_turnover($iban) {
  $query = "SELECT maturitydate FROM turnovers WHERE iban='".mysql_real_escape_string($iban)."' ORDER BY maturitydate DESC LIMIT 1";
  $raw = mysql_query($query);
  $r = mysql_fetch_assoc($raw);
  return date("j. M Y",strtotime($r['maturitydate']));
}

function account_name($iban) {
  $query = "SELECT * FROM accounts WHERE iban='".mysql_real_escape_string($iban)."'";
  $raw = mysql_query($query);
  $r = mysql_fetch_assoc($raw);
  return $r['name'];
}

function account_number($iban) {
  $query = "SELECT * FROM accounts WHERE iban='".mysql_real_escape_string($iban)."'";
  $raw = mysql_query($query);
  $r = mysql_fetch_assoc($raw);
  return $r['account_prefix']." - ".$r['account_number'];
}

function disp_balance_eur($iban) {
  $query = "SELECT * FROM accounts WHERE iban='".mysql_real_escape_string($iban)."'";
  $raw = mysql_query($query);
  $r = mysql_fetch_assoc($raw);
  return $r['disp_balance_eur']." ".$r['currency'];
}

?>
