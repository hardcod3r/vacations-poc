<?php

declare(strict_types=1);

use Domain\Employee\Enum\Role;
use Domain\Vacation\Enum\VacationStatus;
use Faker\Factory as Faker;
use Phinx\Seed\AbstractSeed;

final class DemoSeed extends AbstractSeed
{
    public function run(): void
    {
        $faker = Faker::create();

        // 1 Manager (static credentials for Postman testing)
        $managerId = $faker->uuid;
        $employees = [[
            'id' => $managerId,
            'name' => 'Konstantinos Manager',
            'email' => 'manager@example.com',
            'employee_code' => str_pad((string) $faker->numberBetween(1, 9999999), 7, '0', STR_PAD_LEFT),
            'role' => Role::Manager->value, // 100
        ]];

        // +5 Employees
        for ($i = 0; $i < 5; $i++) {
            $employees[] = [
                'id' => $faker->uuid,
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'employee_code' => str_pad((string) $faker->numberBetween(1, 9999999), 7, '0', STR_PAD_LEFT),
                'role' => Role::Employee->value, // 1
            ];
        }

        // insert employees
        $this->table('employees')->insert($employees)->saveData();

        // ---- CREDENTIALS (manager + employees) ----
        $pdo = $this->getAdapter()->getConnection();

        // static password for manager (if you want to change it, change it here and in Postman env)
        $managerPassword = 'manager123!';
        $managerHash = password_hash($managerPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 1 << 17,
            'time_cost' => 4,
            'threads' => 2,
        ]);

        // default password for employees (if you want to change it, change it here and in Postman env)
        $employeePassword = 'password123!';
        $stmt = $pdo->prepare("
            INSERT INTO employee_credentials (employee_id, password_hash, password_algo, status, updated_at)
            VALUES (:id, :hash, 'argon2id', 1, CURRENT_TIMESTAMP)
            ON CONFLICT (employee_id) DO UPDATE
            SET password_hash = EXCLUDED.password_hash,
                password_algo = EXCLUDED.password_algo,
                status = EXCLUDED.status,
                updated_at = CURRENT_TIMESTAMP
        ");

        // manager
        $stmt->execute([
            ':id' => $managerId,
            ':hash' => $managerHash,
        ]);

        // other employees
        foreach (array_slice($employees, 1) as $emp) {
            $hash = password_hash($employeePassword, PASSWORD_ARGON2ID, [
                'memory_cost' => 1 << 17,
                'time_cost' => 4,
                'threads' => 2,
            ]);
            $stmt->execute([
                ':id' => $emp['id'],
                ':hash' => $hash,
            ]);
        }

        // ---- Vacation Requests demo ----
        $statuses = [
            VacationStatus::Pending,
            VacationStatus::Approved,
            VacationStatus::Rejected,
        ];
        $base = new DateTimeImmutable('+1 week');

        foreach ($statuses as $i => $status) {
            $employee = (array) $faker->randomElement(array_slice($employees, 1)); // skip manager
            $from = $base->modify('+' . ($i * 7) . ' days');
            $to = $from->modify('+5 days');

            $requests[] = [
                'id' => $faker->uuid,
                'employee_id' => $employee['id'],
                'submitted_at' => $faker->dateTimeBetween('-1 month', 'now')->format('c'),
                'from_date' => $from->format('Y-m-d'),
                'to_date' => $to->format('Y-m-d'),
                'reason' => $faker->sentence(6),
                'status' => $status->value,
            ];
        }

        $this->table('vacation_requests')->insert($requests)->saveData();
    }
}
