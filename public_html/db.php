<?php

if(!mysql_connect('localhost','transparent','namestovo')) {
  echo "Problem s pripojenim sa na databazu.";
  die;
}
mysql_select_db('transparent');
mysql_set_charset('utf8');

?>
