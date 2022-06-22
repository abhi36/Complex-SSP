<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
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