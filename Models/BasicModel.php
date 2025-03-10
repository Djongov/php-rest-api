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
    public static function applySortingAndLimiting(string $query, ?string $orderBy = null, ?string $sort = null, ?int $limit = null): string
    {
        if ($orderBy) {
            $query .= " ORDER BY $orderBy " . ($sort ?? "ASC");
        }
        
        if ($limit) {
            $query .= " LIMIT $limit";
        }

        return $query;
    }
}
