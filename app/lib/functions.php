<?php

function slsp_logout($c,$cfile) {
  echo("-- Logging out\n");
  curl_setopt($c, CURLOPT_URL, "https://ib.slsp.sk/ebanking/logout/ibxlogoutyes.xml");
  curl_setopt ($c, CURLOPT_COOKIEFILE, $cfile);
  curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
  $output = curl_exec ($c);

  if (preg_match("<logout/>",$output)) {
    echo("Logout successful\n");
  } else {
    echo("Logout failed!\n");
    die;
  }
}

?>
