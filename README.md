
# DataTables SSP Class

Helper functions for building a DataTables server-side processing (SSP) SQL query.
This class alllows to fetch data from joined tables with super ease.
Highly formattable in-build fields to get data from databases
exactly the way you want.




## Badges


[![GPLv3 License](https://img.shields.io/badge/License-GPL%20v3-yellow.svg)](https://opensource.org/licenses/)


## Usage/Examples

```php
$queryFields = [
    "fields" => [
        [
            "name"      => "T1.field1",
            "filter"    => [] // Optional
            "callback"  => "setTimestamp" // Optional
            "formatter" => "<a href='{{any_field}}'>{{field1}}" //Optional
        ],
        .............
        [
            "name"      => "actions", // Special field entry
            "options"   => ["edit" => "hasEditOption", "delete" => "hasDeleteOption"],
            "filter"    => []
        ],
        [
            "name"      => "silence", // Special field entry.
            "fields"    => ["T2.any_field", "T1.another_field"],
            "filter"    => []
        ],
    ]
    "params" => [
        "joiner" => [
            "table2 AS T2" => "T1.refer_key=T2.primary_key",
            .........
        ],
        "where" => "1",
        "groupBy" => "T1.id",
        "debug" => false
    ]
]
```
Need to add another index called `joinedTbls` to the params of `$queryFields`
```PHP
$joinedClause = "";
$selFields["joiner"] = $selFields["params"]["joiner"];
if(isset($selFields["joiner"]) && is_array($selFields["joiner"])){
    $joinedArr = array_map( function($k, $v){ return "LEFT JOIN {$k} ON ({$v})"; }, array_keys($selFields["joiner"]), $selFields["joiner"] );
    $joinedClause = implode(" ", $joinedArr);
}
$selFields["params"]["joinedTbls"] = $joinedClause;
```
#### NOTE: Number of fields have to be equal to the number of columns on the `<table>`


## Parameters for each fields
* `name` is he mandatory field for each fields
* `filter` should have field name without table alias (eg: T1)
* `callback` function to pre-format column data before rendering
* `formatter` supplied HTML tag to format any column data

#### Note: `callback` is called before `formatter` since both parameters are acceptable on a single field

Special fields with name 'actions' must have the options.
If edit and delete are going to be ebaled for each row.
The values of those indices are the callbacks

The ajax called intermediate PHP script is the right place
to put the callback functions
```PHP
function setEditOption($p){
    // $p the primary key which is only parameter here. Other variables can be obtained with global declarations
}
function setDeleteOption($p){
    // Same as editoption
}
function setTimestamp($cellData, $index, $rowData, $pk){
    return date("jS F, Y \@ H:i:s", strtotime($cellData));
}
```

Create the global JS variale on 
the same page as where the table HTML is placed

```javascript
const dataAttribs = {
    "t": "<?=base64_encode("table AS R") ?>",
    "fields": "<?=base64_encode(serialize($selFields["fields"])) ?>",
    "params": "<?=base64_encode(serialize($selFields["params"])) ?>",
    "key": "R.id"
};
```

Table code on the HTML page
```html
<table class="tables table-responsive table-bordered load-data">
    <thead>
        <tr>
            <th>Field I</th>
            ......
        </tr>
    </thead>
    ......
```
Ajax call to the ajax script that makes the call to SSP
and bring the data to the table

```javascript
const jxUrl = "/path/to/ssp.class.php";
let dtConfig = {};

if($('.table').hasClass('load-data')){
    var jxConfig = {
            serverSide: true,
            processing: true,
            ajax: {
                url: jxUrl,
                type: "POST",
                data: jxData
            }
        };

    $.extend(dtConfig, jxConfig);
}
var table = $('.table').DataTable(dtConfig);
```