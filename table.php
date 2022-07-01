<?php
$editLink = "/add/tableData";
?>
<script>
    const tblSlug = 'table1',
        primaryTbl = 'table1 AS RV',
        whereClause = 1
    let selFields = {
        "fields": [{
                "name": "ST.field1 AS field1",
                "filter": ["ST.field1"],
            },
            {
                "name": "CONCAT(R.name, ' ', R.strength, ' ', R.volume, ' ', R.package) AS field2",
                "filter": ["R.name", "R.strength", "R.volume", "R.package"],
                "formatter": "<em>{{field2}}</em>",
                "callback": "setDataFormat" // Optional | Ignored if function doesn't exist
            },
            {
                "name": "R.field3"
            },
            {
                "name": "R.timestamp",
                "callback": "setTimestamp"
            },
            {
                "name": "operations", // Special field entry
                "options": {
                    "edit": "hasEditOption",
                    "delete": "hasDeleteOption"
                },
                "filter": []
            },
            {
                "name": "silence", // Special field entry.
                "fields": ["RV.created_at"],
                "filter": []
            },
        ],
        "params": {
            "joiner": {
                "table2 AS ST": ["LEFT", "ST.referer_key=R.id"],
                "table3 AS CT": ["INNER", "R.referer_key=CT.id"]
            },
            "where": "1",
            "groupBy": "R.id", // Optional
            "debug": false // true shows the generated query


            "edit": "<?= $addNewSlug ?>",
            "delete": false,
            "formAction": "<?= FORM_ACTION ?>",
        }
    }


    if (selFields !== 'undefined') {
        /**
         * Create the DataAttrib object to pass to Ajax
         */
        // console.log(selFieldss["fields"]);
        var dataAttribs = {
                "t": btoa(primaryTbl),
                "fields": btoa(JSON.stringify(selFieldss["fields"])),
                "params": btoa(JSON.stringify(selFieldss["params"])),
                "key": selFieldss["params"]["key"] || "RV.id",

                "edit": selFieldss["params"]["edit"] || false,
                "delete": selFieldss["params"]["edit"] || false,

                "formAction": selFieldss["params"]["formAction"] || window.location,
            },
    }
</script>

<table class="tables table-responsive table-bordered load-data">
    <thead>
        <tr>
            <th>Field 1</th>
            <th>Field 2</th>
            <th>Field 3</th>
            <th>Timestamp</th>
        </tr>
    </thead>

    <tbody>

    </tbody>

    <tfoot>
        <tr>
            <th>Field 1</th>
            <th>Field 2</th>
            <th>Field 3</th>
            <th>Timestamp</th>
        </tr>
    </tfoot>
</table>
</div>
</div>

</body>

</html>