<?php

namespace App\Database;

use DateTimeInterface;

class PostgresConnection extends \Illuminate\Database\PostgresConnection
{
    /**
     * With PDO::ATTR_EMULATE_PREPARES enabled (required for the Neon/PgBouncer
     * pooler), PDO inlines bound values as literal SQL text instead of typed
     * parameters. Laravel's base prepareBindings() casts booleans to (int),
     * which Postgres then parses as an untyped integer literal and rejects for
     * boolean columns ("column is of type boolean but expression is of type
     * integer"). Native prepared statements avoid this because Postgres infers
     * the parameter type from the column, but emulation bypasses that
     * inference — so we bind text 'true'/'false' instead, which Postgres's
     * boolean input parser accepts.
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = $value ? 'true' : 'false';
            }
        }

        return $bindings;
    }
}
