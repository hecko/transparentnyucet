<hr>
Vygenerovane <? echo date("Y-m-d H:i:s") ?>
  </div>
</div>
<script type="text/javascript">
  $('i').on("mouseover", function() { $(this).tooltip('show')});
  $(document).ready(function() { $('#turnovers').dataTable( 
    {
      "sDom": "<'row'<'span9'><'span9'f>r>t<'row'<'span6'i><'span7'p>>",
      "sPaginationType": "bootstrap",
      "aaSorting":[],
      "oLanguage": {
        "sLengthMenu": "Zobrazit _MENU_ poloziek",
        "sZeroRecords": "Ziadny zaznam nebol najdeny.",
        "sInfo": "Prezerate si pohyb _START_ az _END_ z celkovych _TOTAL_",
        "sInfoEmpty": "Showing 0 to 0 of 0 records",
        "sInfoFiltered": "(filtered from _MAX_ total records)",
        "sSearch": "Hladat: ",
        "sPageNext": "Dalsia",
        "sPagePrevious": "Predchadzajuca"
      },
      "iDisplayLength": 20
    } 
  ); });
  $.extend( $.fn.dataTableExt.oStdClasses, {
    "sWrapper": "dataTables_wrapper form-inline"
  } );
</script>
</body>
</html>
