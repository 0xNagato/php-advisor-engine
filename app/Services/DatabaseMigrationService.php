<?php

namespace App\Services;

use App\Filament\Pages\DatabaseComparison;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseMigrationService
{
    /**
     * Resolve foreign key dependencies.
     */
    public function getTableDependencies(): array
    {
        $results = DB::connection('mysql')
            ->table('information_schema.key_column_usage')
            ->select('table_name', 'referenced_table_name')
            ->whereNotNull('referenced_table_name')
            ->whereRaw("table_name NOT LIKE 'cache%'")
            ->whereRaw("table_name NOT LIKE 'pulse_%'")
            ->whereRaw("table_name NOT LIKE 'telescope_%'")
            ->whereRaw("table_name NOT LIKE 'schedule%with_%'")
            ->whereRaw("table_name NOT LIKE 'short%'")
            ->where('constraint_schema', config('database.connections.mysql.database'))
            ->get();

        $dependencies = [];
        foreach ($results as $result) {
            $dependencies[$result->TABLE_NAME][] = $result->REFERENCED_TABLE_NAME;
        }

        return $dependencies;
    }

    /**
     * Topological sort of tables based on foreign key dependencies.
     */
    public function sortTablesByDependencies(array $dependencies): array
    {
        $sorted = [];
        $visited = [];

        $visit = function ($table) use (&$sorted, &$visited, &$visit, $dependencies) {
            if (isset($visited[$table])) {
                return;
            }

            $visited[$table] = true;

            if (isset($dependencies[$table])) {
                foreach ($dependencies[$table] as $dependency) {
                    $visit($dependency);
                }
            }

            $sorted[] = $table;
        };

        foreach (array_keys($dependencies) as $table) {
            $visit($table);
        }

        return array_reverse(array_unique($sorted));
    }

    /**
     * Migrate data for a given table with batching.
     *
     * @throws Throwable
     */
    public function migrateTable(string $table, int $batchSize): array
    {
        try {
            $offset = 0;
            $dataCount = 0;

            // Start message for migrating table
            logger()->info("Starting migration for table '$table' with staggered inserts...");

            $recordCount = DB::connection('pgsql')->table($table)->count();
            if ($recordCount > 0) {
                // Truncate PostgreSQL table to clear existing data
                DB::connection('pgsql')->table($table)->truncate();
            }

            // Disable foreign key checks and reset ID sequence
            //            $this->disableForeignKeyChecks();
            $this->disableIdSequence($table);

            // Fetch and insert in batches
            while (true) {
                // Fetch small batch from MySQL
                $data = DB::connection('mysql')->table($table)
                    ->offset($offset)
                    ->limit($batchSize)
                    ->get();

                if ($data->isEmpty()) {
                    // End loop if no more data
                    logger()->info("No more records to fetch for table '$table'.");
                    break;
                }

                // Format data for insertion
                $formattedData = $data->map(fn ($row) => (array) $row)->toArray();

                // Log fetched batch size for debugging
                logger()->info('Fetched batch of '.count($formattedData)." records from table '$table'.");

                // Insert into PostgreSQL (with transaction for safety)
                try {
                    DB::connection('pgsql')->transaction(function () use ($formattedData, $table) {
                        DB::connection('pgsql')->table($table)->insert($formattedData);
                    });
                    $dataCount += count($formattedData);
                    logger()->info("Migrated $dataCount records for table '$table'.");
                } catch (Exception|Throwable $e) {
                    // Log batch insert failure
                    logger()->error("Insert failed for table '$table': ".$e->getMessage());
                    throw $e;
                }

                // Increment offset and stagger inserts
                $offset += $batchSize;
                usleep(50000); // Pause for 50ms between batches
            }

            // Re-enable foreign key checks and reset ID sequence
            $this->resetIdSequence($table);
            //            $this->enableForeignKeyChecks();

            return [
                'status' => 'success',
                'message' => "Data for table '$table' migrated successfully.",
            ];
        } catch (Exception $e) {
            //            $this->enableForeignKeyChecks(); // Ensure FK checks are on

            logger()->error("Error migrating table '$table': ".$e->getMessage());

            return [
                'status' => 'error',
                'message' => "Error migrating table '$table': ".$e->getMessage(),
            ];
        }
    }

    /**
     * Get a list of MySQL tables, filtered to exclude specified patterns.
     */
    public function getFilteredTables(): array
    {
        return DB::connection('mysql')
            ->table('information_schema.tables')
            ->select('table_name')
            ->where('table_schema', config('database.connections.mysql.database'))
//            ->whereRaw("table_name NOT LIKE 'cache%'")
            ->whereRaw("table_name NOT LIKE 'pulse_%'")
            ->whereRaw("table_name NOT LIKE 'telescope_%'")
            ->whereRaw("table_name NOT LIKE 'schedule%with_%'")
            ->whereRaw("table_name NOT LIKE 'password%reset%'")
            ->whereRaw("table_name NOT LIKE 'short%'")
            ->get()
            ->pluck('TABLE_NAME') // Ensure you use the correct column name (lowercase `table_name`)
            ->toArray();
    }

    /**
     * Compare data between MySQL and PostgreSQL for the given paginated set of tables.
     */
    public function compareTables($paginatedTables): array
    {
        $comparisons = [];

        foreach ($paginatedTables as $table) {
            try {
                // Row count comparison for the table
                $mysqlCount = DB::connection('mysql')->table($table)->count();
                $pgsqlCount = DB::connection('pgsql')->table($table)->count();

                $comparison = [
                    'is_equal' => $this->isTableDataEqual($table), // Compare detailed data
                    'mysql_row_count' => $mysqlCount,
                    'pgsql_row_count' => $pgsqlCount,
                    'row_difference' => abs($mysqlCount - $pgsqlCount),
                ];

                // Add to results
                $comparisons[$table] = $comparison;
            } catch (Exception $e) {
                // Handle errors gracefully and log them
                logger()->error("Error comparing table '$table': ".$e->getMessage());

                $comparisons[$table] = [
                    'is_equal' => false,
                    'mysql_row_count' => null,
                    'pgsql_row_count' => null,
                    'row_difference' => 'Error',
                ];
            }
        }

        return $comparisons;
    }

    /**
     * Paginate the list of tables.
     */
    public function paginateTables(array $tables, int $perPage, Request $request): LengthAwarePaginator
    {
        $currentPage = $request->input('page', 1);

        return new LengthAwarePaginator(
            array_slice($tables, ($currentPage - 1) * $perPage, $perPage),
            count($tables),
            $perPage,
            $currentPage,
            ['path' => DatabaseComparison::getUrl(), 'query' => $request->query()]
        );
    }

    /**
     * Check if the data between a table in MySQL and PostgreSQL is equal.
     */
    public function isTableDataEqual(string $table): bool
    {
        try {
            // Try to fetch primary key(s) for the table in MySQL
            $primaryKey = $this->getTablePrimaryKey('mysql', $table);

            // Fallback logic if no primary key is detected
            if (! $primaryKey) {
                // Compare only the total row count since no `MIN` or `MAX` can be used
                $mysqlCount = DB::connection('mysql')->table($table)->count();
                $pgsqlCount = DB::connection('pgsql')->table($table)->count();

                return $mysqlCount === $pgsqlCount;
            }

            // Fetch summary stats for MySQL using the primary key
            $mysqlData = DB::connection('mysql')
                ->table($table)
                ->selectRaw("COUNT(*) as total, MIN($primaryKey) as first_id, MAX($primaryKey) as last_id")
                ->first();

            // Fetch summary stats for PostgreSQL using the primary key
            $pgsqlData = DB::connection('pgsql')
                ->table($table)
                ->selectRaw("COUNT(*) as total, MIN($primaryKey) as first_id, MAX($primaryKey) as last_id")
                ->first();

            return $mysqlData == $pgsqlData;
        } catch (Exception $e) {
            // Log the exception for debugging purposes
            logger()->error("Error comparing table data for '$table': ".$e->getMessage());

            return false;
        }
    }

    /**
     * Get the primary key of a given table from the database schema.
     */
    protected function getTablePrimaryKey(string $connection, string $table): ?string
    {
        try {
            // Query the information schema to fetch primary key column(s)
            $primaryKeyInfo = DB::connection($connection)
                ->table('information_schema.key_column_usage')
                ->where('table_schema', config("database.connections.$connection.database"))
                ->where('table_name', $table)
                ->where('constraint_name', 'PRIMARY')
                ->select('column_name')
                ->first();

            // Return the primary key column name, or null if no primary key exists
            return $primaryKeyInfo->column_name ?? null;
        } catch (Exception $e) {
            // Log the exception for debugging purposes
            logger()->error("Error fetching primary key for table '$table': ".$e->getMessage());

            return null;
        }
    }

    /**
     * Disable foreign key checks in PostgreSQL.
     */
    private function disableForeignKeyChecks(): void
    {
        DB::connection('pgsql')->statement("SET session_replication_role = 'replica';");
    }

    /**
     * Enable foreign key checks in PostgreSQL.
     */
    private function enableForeignKeyChecks(): void
    {
        DB::connection('pgsql')->statement("SET session_replication_role = 'origin';");
    }

    /**
     * Disable the ID sequence for a table if it exists in PostgreSQL.
     */
    private function disableIdSequence(string $table): void
    {
        try {
            $sequenceName = $this->getIdSequenceName($table);

            if ($sequenceName && $this->doesSequenceExist($sequenceName)) {
                DB::connection('pgsql')->statement("ALTER SEQUENCE $sequenceName NO MINVALUE NO MAXVALUE");
                logger()->info("ID sequence '$sequenceName' disabled for table '$table'.");
            } else {
                logger()->warning("Sequence for table '$table' does not exist or cannot be determined. Skipping.");
            }
        } catch (Exception $e) {
            logger()->error("Error disabling ID sequence for table '$table': ".$e->getMessage());
        }
    }

    /**
     * Reset the ID sequence for a PostgreSQL table.
     */
    private function resetIdSequence(string $table): void
    {
        try {
            $sequenceName = $this->getIdSequenceName($table);

            if ($sequenceName && $this->doesSequenceExist($sequenceName)) {
                DB::connection('pgsql')->statement("SELECT setval('$sequenceName', COALESCE(MAX(id), 1)) FROM $table");
                logger()->info("ID sequence '$sequenceName' reset for table '$table'.");
            } else {
                logger()->warning("Sequence for table '$table' does not exist or cannot be determined. Skipping.");
            }
        } catch (Exception $e) {
            logger()->error("Error resetting ID sequence for table '$table': ".$e->getMessage());
        }
    }

    /**
     * Get the ID sequence name for a table in PostgreSQL.
     */
    private function getIdSequenceName(string $table): ?string
    {
        try {
            $schema = config('database.connections.pgsql.schema', 'public');

            $result = DB::connection('pgsql')->selectOne("
            SELECT pg_get_serial_sequence('\"$schema\".\"$table\"', 'id') AS sequence_name
        ");

            return $result->sequence_name ?? null;
        } catch (Exception $e) {
            logger()->error("Error retrieving sequence name for table '$table': ".$e->getMessage());

            return null;
        }
    }

    /**
     * Check if a given sequence exists in PostgreSQL.
     */
    protected function doesSequenceExist(string $sequenceName): bool
    {
        try {
            $result = DB::connection('pgsql')->selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM pg_class
                WHERE relkind = 'S' AND relname = ?
            ) AS exists
        ", [$sequenceName]);

            return $result->exists ?? false;
        } catch (Exception $e) {
            logger()->error("Error checking existence of sequence '$sequenceName': ".$e->getMessage());

            return false;
        }
    }

    /**
     * Log progress for large tables during migration.
     */
    private function logProgress(string $table, int $offset): void
    {
        logger()->info("Migrated $offset records from table '$table'.");
    }
}
