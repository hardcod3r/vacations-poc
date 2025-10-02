<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateVacationRequestsTable extends AbstractMigration
{
    public function up(): void
    {
        // needed for EXCLUDE
        $this->execute('CREATE EXTENSION IF NOT EXISTS btree_gist;');

        $this->table('vacation_requests', [
            'id' => false,
            'primary_key' => 'id',
        ])
            ->addColumn('id', 'string', [
                'limit' => 36,
            ])
            ->addColumn('employee_id', 'string', [
                'limit' => 36,
            ])
            ->addColumn('submitted_at', 'timestamp')
            ->addColumn('from_date', 'date')
            ->addColumn('to_date', 'date')
            ->addColumn('reason', 'text')
            ->addColumn('status', 'integer', [
                'limit' => 1,
                'default' => 0,
            ])
            ->addIndex([
                'employee_id',
            ])
            ->addIndex([
                'status',
            ])
            ->addIndex([
                'employee_id',
                'status',
                'from_date',
            ])
            ->addForeignKey('employee_id', 'employees', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
            ])
            ->create();

        // timestamp -> timestamptz
        $this->execute("ALTER TABLE vacation_requests ALTER COLUMN submitted_at TYPE timestamptz(0) USING date_trunc('second', submitted_at AT TIME ZONE 'UTC')");
        // CHECK constraint
        $this->execute('
            ALTER TABLE vacation_requests
            ADD CONSTRAINT chk_vacations_from_le_to CHECK (from_date <= to_date)
        ');

        // EXCLUDE for overlaps
        $this->execute("
            ALTER TABLE vacation_requests
            ADD CONSTRAINT no_overlapping_vacations
            EXCLUDE USING gist (
              employee_id WITH =,
              daterange(from_date, to_date, '[]') WITH &&
            )
            WHERE (status IN (0,1))
        ");
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS vacation_requests CASCADE');
    }
}
