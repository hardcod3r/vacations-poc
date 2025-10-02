<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEmployeeAuthTables extends AbstractMigration
{
    public function up(): void
    {
        // employee_credentials
        $this->table('employee_credentials', [
            'id' => false,
            'primary_key' => 'employee_id',
        ])
            ->addColumn('employee_id', 'string', [
                'limit' => 36,
            ])
            ->addColumn('password_hash', 'string', [
                'limit' => 255,
            ])
            ->addColumn('password_algo', 'string', [
                'limit' => 32,
                'default' => 'argon2id',
            ])
            ->addColumn('status', 'integer', [
                'limit' => 1,
                'default' => 1,
            ])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['employee_id'], [
                'unique' => true,
            ])
            ->addForeignKey('employee_id', 'employees', 'id', [
                'delete' => 'CASCADE',
            ])
            ->create();

        // timestamp -> timestamptz
        $this->execute(
            <<<'SQL'
            ALTER TABLE employee_credentials
            ALTER COLUMN updated_at
            TYPE timestamptz(0)
            USING date_trunc('second', updated_at);
            SQL,
        );

        // refresh_tokens
        $this->table('refresh_tokens', [
            'id' => false,
            'primary_key' => 'id',
        ])
            ->addColumn('id', 'string', [
                'limit' => 36,
            ])
            ->addColumn('employee_id', 'string', [
                'limit' => 36,
            ])
            ->addColumn('issued_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('expires_at', 'timestamp')
            ->addColumn('revoked_at', 'timestamp', [
                'null' => true,
            ])
            ->addColumn('rotated_to', 'string', [
                'limit' => 36,
                'null' => true,
            ])
            ->addIndex(['employee_id', 'expires_at'])
            ->addForeignKey('employee_id', 'employees', 'id', [
                'delete' => 'CASCADE',
            ])
            ->create();

        // timestamp -> timestamptz + default
        $this->execute(
            <<<'SQL'
            ALTER TABLE refresh_tokens
              ALTER COLUMN issued_at  TYPE timestamptz(0) USING date_trunc('second', issued_at),
              ALTER COLUMN expires_at TYPE timestamptz(0) USING date_trunc('second', expires_at),
              ALTER COLUMN revoked_at TYPE timestamptz(0) USING date_trunc('second', revoked_at);

            ALTER TABLE refresh_tokens
              ALTER COLUMN issued_at SET DEFAULT CURRENT_TIMESTAMP(0);
            SQL,
        );

        // self-FK (rotation chain)
        $this->execute(
            <<<'SQL'
            ALTER TABLE refresh_tokens
            ADD CONSTRAINT fk_rtok_rotated_to
            FOREIGN KEY (rotated_to) REFERENCES refresh_tokens(id)
            ON DELETE SET NULL;
            SQL,
        );
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS refresh_tokens CASCADE');
        $this->execute('DROP TABLE IF EXISTS employee_credentials CASCADE');
    }
}
