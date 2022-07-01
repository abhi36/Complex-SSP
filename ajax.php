<script>
    let jxData = {},
        jxUrl = "";

    if (typeof dataAttribs !== 'undefined') {
        jxData = dataAttribs;
        jxUrl = 'ajax-data.php';
    }

    if ($('.table').hasClass('load-data')) {
        var jxConfig = {
            serverSide: true,
            processing: true,
            ajax: {
                url: jxUrl,
                type: "POST",
                data: jxData,
            }
        };

        $.extend(dtConfig, jxConfig);
    }

    var table = $('.table').DataTable(dtConfig);
</script>