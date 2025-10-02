<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEmployeesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('employees', [
            'id' => false,
            'primary_key' => 'id',
        ])
            ->addColumn('id', 'string', [
                'limit' => 36,
            ])
            ->addColumn('name', 'string', [
                'limit' => 100,
            ])
            ->addColumn('email', 'string', [
                'limit' => 150,
            ])
            ->addColumn('employee_code', 'string', [
                'limit' => 7,
            ])
            ->addColumn('role', 'integer', [
                'limit' => 3,
                'default' => 1,
            ]) // 1=employee, 100=manager
            ->addIndex([
                'email',
            ], [
                'unique' => true,
            ])
            ->addIndex([
                'employee_code',
            ], [
                'unique' => true,
            ])
            ->create();
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS employees CASCADE');
    }
}
