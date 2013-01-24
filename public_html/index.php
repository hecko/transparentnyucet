<?
include('header.php');
?>
<table class="table-hover table-condensed">
<?
//list accounts
$query = "SELECT iban FROM turnovers GROUP BY iban";
$raw = mysql_query($query);
while ($r = mysql_fetch_array($raw)) {
?>
  <tr>
    <td><? echo account_number($r['iban']) ?></td>
    <td><a href="turnovers.php?iban=<? echo $r['iban'] ?>"><? echo account_name($r['iban']); ?></a></td>
    <td><small>posledny pohyb: <? echo last_turnover($r['iban']); ?></small></td>
  </tr>
<?
}
?>
</table>
<?
include('footer.php');
?>
