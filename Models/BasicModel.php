<?php declare(strict_types=1);

namespace Models;

use App\Database\DB;

class BasicModel
{
    // get columns from the table
    public function getColumns(string $table) : array
    {
        $db = new DB();
        $describeArray = $db->describe($table);
        $columns = [];
        foreach ($describeArray as $column => $type) {
            array_push($columns, $column);
        }
        return $columns;
    }
}
