<?
include('header.php');
$iban = $_GET['iban'];
$sql = "SELECT * FROM turnovers WHERE iban='".mysql_real_escape_string($iban)."' ORDER BY maturitydate DESC LIMIT 100";
$raw = mysql_query($sql);
?>
    <h6>posledna synchronizacia: <? echo last_sync(); ?>; <a href="index.php">spat na zoznam transparentnych uctov</a></h6>
    <p><i>
      Cislo uctu: <? echo account_number($iban) ?><br>
      Disponibilny zostatok na ucte: <? echo disp_balance_eur($iban); ?>
    </i></p>
        <table class="table table-hover table-striped table-bordered table-condensed" id="turnovers">
        <thead><tr>
          <th style="width: 70pt">datum</th>
          <th>poznamka</th>
          <th style="text-align: right">KS</th>
          <th style="width: 50pt; text-align: right">suma</th>
          <th style="text-align: center">typ</th>
        </tr></thead>
        <tbody>
        <?php
        while ($r = mysql_fetch_assoc($raw)) {
          if ($r['note'] == '') {
            $r['note'] = '&lt;bez poznamky&gt;';
          }
          if ($r['description'] == 'Trvalý príkaz na úhradu') {
            $description['icon'] = 'class="icon-retweet" data-title="'.$r['description'].'" data-placement="right"';
            $description['text'] = '';
          } elseif ($r['description'] == 'Bezhotovostný vklad') {
            $description['icon'] = 'class="icon-download-alt " data-title="'.$r['description'].'" data-placement="right"';
            $description['text'] = '';
          } elseif ($r['description'] == 'Príkaz na úhradu (file transfer)') {
            $description['icon'] = 'class="icon-folder-open" data-title="'.$r['description'].'" data-placement="right"';
            $description['text'] = '';
          } elseif ($r['description'] == 'Príkaz na úhradu (EB)') {
            $description['icon'] = 'class="icon-arrow-right" data-title="'.$r['description'].'" data-placement="right"';
            $description['text'] = '';
          } else {
            $description['icon'] = '';
            $description['text'] = $r['description'];
          }
          if ($r['amount']<=0) {
            $amount_class = 'text-error';
          } else {
            $amount_class = 'text-info';
          }
          if (isset($datumy[$r['maturitydate']])) {
            //$maturitydate = '';
            $maturitydate = date("j. M Y",strtotime($r['maturitydate']));
          } else {
            $datumy[$r['maturitydate']] = "";
            $maturitydate = date("j. M Y",strtotime($r['maturitydate']));
          }
          ?>
          <tr>
          <td><? echo $maturitydate ?></td>
          <td><? echo $r['note'] ?></td>
          <td style="text-align: right"><? echo $r['constantsymb'] ?></td>
          <td style="text-align: right"><b><span class="<? echo $amount_class ?>"><? echo number_format($r['amount'],2) ?> <? echo $r['currency'] ?></span></b></td>
          <td style="text-align: center"><? echo $description['text'] ?><i <? echo $description['icon']; ?>></i></td>
          </tr>
          <?
        }
        ?>
        </tbody></table>
<?
include('footer.php');
?>
