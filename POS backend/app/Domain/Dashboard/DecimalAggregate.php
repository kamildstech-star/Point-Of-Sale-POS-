<?php

namespace App\Domain\Dashboard;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** MySQL DECIMAL aggregates; BCMath-backed SQLite functions for isolated tests. */
final class DecimalAggregate
{
    private bool $sqlite;

    public function __construct()
    {
        $this->sqlite = DB::connection()->getDriverName() === 'sqlite';
        if ($this->sqlite) {
            $pdo = DB::connection()->getPdo();
            $aggregate = method_exists($pdo, 'createAggregate') ? 'createAggregate' : 'sqliteCreateAggregate';
            $function = method_exists($pdo, 'createFunction') ? 'createFunction' : 'sqliteCreateFunction';
            $collation = method_exists($pdo, 'createCollation') ? 'createCollation' : 'sqliteCreateCollation';
            $pdo->$aggregate('pos_decimal_sum', static fn ($sum, $row, $value) => bcadd($sum ?? '0', $value ?? '0', 4), static fn ($sum, $rows) => $sum ?? '0', 1);
            $pdo->$function('pos_decimal_sub', static fn ($a, $b) => bcsub((string) $a, (string) $b, 4), 2);
            $pdo->$function('pos_decimal_negate', static fn ($a) => bcsub('0', (string) $a, 4), 1);
            $pdo->$function('pos_balance_due', static function ($grand, $returned, $paid, $refunded, $reverse) {
                $due = bcadd(bcsub(bcsub((string) $grand, (string) $returned, 4), (string) $paid, 4), (string) $refunded, 4);
                if ($reverse) {
                    $due = bcsub('0', $due, 4);
                }

                return bccomp($due, '0', 4) > 0 ? $due : '0';
            }, 5);
            $pdo->$collation('pos_decimal', static fn ($a, $b) => bccomp($a, $b, 4));
        }
    }

    public function sumSql(string $expression): string
    {
        return $this->sqlite ? 'COALESCE(pos_decimal_sum(CAST('.$expression.' AS TEXT)), \'0\')' : 'COALESCE(SUM('.$expression.'), 0)';
    }

    public function subtractSql(string $a, string $b): string
    {
        return $this->sqlite ? 'pos_decimal_sub('.$a.', '.$b.')' : '('.$a.' - '.$b.')';
    }

    public function negativeSql(string $column): string
    {
        return $this->sqlite ? 'pos_decimal_negate(CAST('.$column.' AS TEXT))' : '(-'.$column.')';
    }

    public function orderDecimal(Builder $query, string $alias): Builder
    {
        return $query->orderByRaw($alias.($this->sqlite ? ' COLLATE pos_decimal' : '').' DESC');
    }

    public function dueSql(bool $reverse = false): string
    {
        return $this->sqlite ? 'pos_balance_due(CAST(grand_total AS TEXT), CAST(returned_total AS TEXT), CAST(paid_total AS TEXT), CAST(total_refunded AS TEXT), '.($reverse ? '1' : '0').')'
            : 'GREATEST('.($reverse ? '-1 * ' : '').'(grand_total - returned_total - paid_total + total_refunded), 0)';
    }

    public function totals(Builder $query, array $columns): array
    {
        $query = clone $query;
        $select = ['COUNT(*) AS aggregate_count'];
        foreach ($columns as $alias => $column) {
            $select[] = $this->sumSql($column).' AS '.$alias;
        }
        $row = $query->reorder()->selectRaw(implode(', ', $select))->first();
        $result = ['count' => (int) $row->aggregate_count];
        foreach ($columns as $alias => $_) {
            $result[$alias] = bcadd((string) ($row->$alias ?? '0'), '0', 4);
        }

        return $result;
    }

    public function grouped(Builder $query, array $columns, string $group, array $bindings = []): array
    {
        $query = clone $query;
        $query->reorder()->selectRaw($group.' AS dashboard_group', $bindings)->groupBy('dashboard_group');
        foreach ($columns as $alias => $column) {
            $query->selectRaw($this->sumSql($column).' AS '.$alias);
        }
        $result = [];
        foreach ($query->get() as $row) {
            $result[(string) $row->dashboard_group] = [];
            foreach ($columns as $alias => $_) {
                $result[(string) $row->dashboard_group][$alias] = bcadd((string) $row->$alias, '0', 4);
            }
        }

        return $result;
    }
}
