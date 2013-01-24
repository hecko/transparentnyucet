<?php

function slsp_logout($c,$cfile,$log) {
  $log->logInfo('-- Logging out');
  curl_setopt($c, CURLOPT_URL, "https://ib.slsp.sk/ebanking/logout/ibxlogoutyes.xml");
  curl_setopt ($c, CURLOPT_COOKIEFILE, $cfile);
  curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
  $output = curl_exec ($c);

  if (preg_match("<logout/>",$output)) {
    $log->logInfo('Logout successful');
  } else {
    $log->logInfo('Logout failed!');
    die;
  }
}

?>
