<?php
    $editLink = "/add/tableData";

    $selFields = [
        "fields" => [
            [
                "name"      => "ST.field1 AS field1",
                "filter"    => ["ST.field1"],
            ],
            [
                "name"      => "CONCAT(R.name, ' ', R.strength, ' ', R.volume, ' ', R.package) AS field2",
                "filter"    => ["R.name", "R.strength", "R.volume", "R.package"],
                "formatter" => "<em>{{field2}}</em>",
                "callback"  => "setDataFormat" // Optional | Ignored if function doesn't exist
            ],
            [
                "name"      => "R.field3"
            ],
            [
                "name"      => "R.timestamp",
                "callback"  => "setTimestamp"
            ],
            [
                "name"      => "actions", // Special field entry
                "options"   => ["edit" => "setEditOption", "delete" => "setDeleteOption"],
                "filter"    => []
            ],
            [
                "name"      => "silence", // Special field entry
                "fields"    => [],
                "filter"    => []
            ]
        ],
        "params" => [
            "joiner" => [
                "table2 AS ST" => ["LEFT", "ST.referer_key=R.id"],
                "table3 AS CT" => ["INNER", "R.referer_key=CT.id"]
            ],
            "where" => "1",
            "groupBy" => "R.id", // Optional
            "debug" => false // true shows the generated query
        ]
    ];

    $joinedClause = "";
    $selFields["joiner"] = $selFields["params"]["joiner"];
    if(isset($selFields["joiner"]) && is_array($selFields["joiner"])){
        $joinedArr = array_map( function($k, $v){ return "{$v[0]} JOIN {$k} ON ({$v[1]})"; }, array_keys($selFields["joiner"]), $selFields["joiner"] );
        $joinedClause = implode(" ", $joinedArr);
    }
    $selFields["params"]["joinedTbls"] = $joinedClause;
?>
        <script>
            const dataAttribs = {
                "t": "<?=base64_encode(buildName($tableSlug) . " AS R") ?>",
                "fields": "<?=base64_encode(serialize($selFields["fields"])) ?>",
                "params": "<?=base64_encode(serialize($selFields["params"])) ?>",
                "key": "R.id",
                
                <?php if($canEdit && isset($editLink)){ ?>
                "edit": "<?=$editLink ?>",
                <?php } ?>
                <?php if($canDelete){ ?>
                "delete": true,
                <?php } ?>

                "formAction": '/admin/panels/manage/list/report/medicines_test.php'
            };
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