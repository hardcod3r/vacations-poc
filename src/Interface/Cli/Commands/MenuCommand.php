<?php

declare(strict_types=1);

namespace Interface\Cli\Commands;

use GuzzleHttp\Client;
use PhpSchool\CliMenu\Action\GoBackAction;
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\CliMenu;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'menu', description: 'Interactive CLI for Vacation API')]
final class MenuCommand extends Command
{
    private Client $http;

    private string $baseUrl;

    private string $sessionFile;

    protected function configure(): void
    {
        $this->baseUrl = \rtrim(\getenv('API_BASE_URL') ?: 'http://nginx/api/v1', '/');
        $this->sessionFile = \getcwd() . '/.cli-session.json';
        $this->http = new Client(['http_errors' => false, 'timeout' => 15]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $w = $this->termWidth();

        (new CliMenuBuilder())
            ->setTitle('Vacation CLI')
            ->setWidth($w)->setMargin(1)->setPadding(1)

            // AUTH
            ->addSubMenu('Auth', function (CliMenuBuilder $b) use ($w) {
                $b->setTitle('Auth')->setWidth($w)->setMargin(1)->setPadding(1)
                    ->addItem('Login (select user)', fn (CliMenu $m) => $this->loginSelectUser($m))
                    ->addItem('Refresh', fn (CliMenu $m) => $this->authRefresh($m))
                    ->addItem('Logout', fn (CliMenu $m) => $this->authLogout($m))
                    ->addItem('Back', new GoBackAction());
            })

            // EMPLOYEES
            ->addSubMenu('Employees', function (CliMenuBuilder $b) use ($w) {
                $b->setTitle('Employees')->setWidth($w)->setMargin(1)->setPadding(1)
                    ->addItem('List', fn (CliMenu $m) => $this->employeesList($m))
                    ->addItem('Show', fn (CliMenu $m) => $this->employeeShow($m))
                    ->addItem('Create', fn (CliMenu $m) => $this->employeeCreate($m))
                    ->addItem('Update', fn (CliMenu $m) => $this->employeeUpdate($m))
                    ->addItem('Delete', fn (CliMenu $m) => $this->employeeDelete($m))
                    ->addItem('Back', new GoBackAction());
            })

            // VACATIONS
            ->addSubMenu('Vacations', function (CliMenuBuilder $b) use ($w) {
                $b->setTitle('Vacations')->setWidth($w)->setMargin(1)->setPadding(1)
                    ->addItem('Submit', fn (CliMenu $m) => $this->vacationSubmit($m))
                    ->addItem('List pending', fn (CliMenu $m) => $this->vacationsPending($m))
                    ->addItem('List by employee', fn (CliMenu $m) => $this->vacationsByEmployee($m))
                    ->addItem('Approve', fn (CliMenu $m) => $this->vacationApprove($m))
                    ->addItem('Reject', fn (CliMenu $m) => $this->vacationReject($m))
                    ->addItem('Delete own', fn (CliMenu $m) => $this->vacationDeleteOwn($m))
                    ->addItem('Back', new GoBackAction());
            })

            // SYSTEM
            ->addSubMenu('System', function (CliMenuBuilder $b) use ($w) {
                $b->setTitle('System')->setWidth($w)->setMargin(1)->setPadding(1)
                    ->addItem('Health check', fn (CliMenu $m) => $this->healthCheck($m))
                    ->addItem('Back', new GoBackAction());
            })

            ->build()
            ->open();

        return Command::SUCCESS;
    }

    // ───────── helpers ─────────

    private function termWidth(): int
    {
        $c = (int) (\getenv('COLUMNS') ?: 0);

        return $c > 40 ? $c : 100;
    }

    /** @return array<string,string> */
    private function headers(?string $overrideToken = null): array
    {
        $token = $overrideToken ?? $this->session('access_token');

        return \is_string($token) ? ['Authorization' => 'Bearer ' . $token] : [];
    }

    /** @return array<string,mixed>|mixed|null */
    private function session(?string $key = null): mixed
    {
        if (!\is_file($this->sessionFile)) {
            return $key ? null : [];
        }
        /** @var array<string,mixed>|null $data */
        $data = \json_decode((string) \file_get_contents($this->sessionFile), true);

        if (!\is_array($data)) {
            return $key ? null : [];
        }

        return $key ? ($data[$key] ?? null) : $data;
    }

    /** @param array<string,mixed> $data */
    private function saveSession(array $data): void
    {
        \file_put_contents(
            $this->sessionFile,
            \json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private function tableString(array $headers, array $rows): string
    {
        $buf = new BufferedOutput();
        (new Table($buf))->setHeaders($headers)->setRows($rows)->render();

        return \rtrim($buf->fetch());
    }

    /**
     * @param  array<string,mixed>  $body
     * @param  list<int>  $ok
     */
    private function statusLine(int $code, array $body, array $ok = [200]): string
    {
        $okb = \in_array($code, $ok, true);
        $label = $okb ? 'SUCCESS' : 'FAIL';
        $color = $okb ? '32' : '31';
        $msg = isset($body['message']) && \is_string($body['message'])
            ? $body['message']
            : ($okb ? 'OK' : 'ERROR');

        return "\e[{$color}m{$label}\e[0m [{$code}] {$msg}";
    }

    /** @param list<string> $lines */
    private function viewLines(CliMenu $parent, string $title, array $lines): void
    {
        $b = (new CliMenuBuilder())
            ->setTitle($title)
            ->setWidth($this->termWidth())->setMargin(1)->setPadding(1);

        foreach ($lines as $ln) {
            $b->addStaticItem($ln === '' ? ' ' : $ln);
        }
        $b->build()->open();
    }

    private function viewText(CliMenu $parent, string $title, string $text): void
    {
        $this->viewLines($parent, $title, \explode("\n", \rtrim($text)));
    }

    /** safe string cast for mixed */
    private function str(mixed $v): string
    {
        return \is_scalar($v) ? (string) $v : '';
    }

    private function issueDevJwt(string $sub, int $role, int $ttl = 3600): ?string
    {
        $priv = \getenv('JWT_PRIVATE_KEY_PATH') ?: '';
        $kid = \getenv('JWT_KID') ?: 'cli';
        $iss = \getenv('JWT_ISS') ?: 'vacation-api';

        if (!\is_readable($priv)) {
            return null;
        }
        $key = (string) \file_get_contents($priv);

        $hdr = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => $kid];
        $now = \time();
        $pld = ['iss' => $iss, 'sub' => $sub, 'role' => $role, 'iat' => $now, 'exp' => $now + $ttl];

        $b64 = static fn (mixed $x): string => \rtrim(
            \strtr(
                \base64_encode(
                    \json_encode($x, JSON_UNESCAPED_SLASHES) ?: '',
                ),
                '+/',
                '-_',
            ),
            '=',
        );

        $unsigned = $b64($hdr) . '.' . $b64($pld);

        $sig = '';

        if (!\openssl_sign($unsigned, $sig, $key, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        if (!\is_string($sig)) {
            return null;
        }

        return $unsigned . '.' . \rtrim(\strtr(\base64_encode($sig), '+/', '-_'), '=');

    }

    private function mgrToken(): ?string
    {
        return $this->issueDevJwt('CLI', 100, 300);
    }

    // ───────── actions (Auth) ─────────

    private function healthCheck(CliMenu $menu): void
    {
        $r = $this->http->get($this->baseUrl . '/health');
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $body = \is_array($body) ? $body : [];
        $lines = [$this->statusLine($r->getStatusCode(), $body, [200])];

        if (isset($body['data'])) {
            $lines[] = '';
            $lines[] = (string) \json_encode($body['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        $this->viewLines($menu, 'Health', $lines);
    }

    private function loginSelectUser(CliMenu $menu): void
    {
        $mgr = $this->mgrToken();

        if (!$mgr) {
            $this->viewText($menu, 'Login', 'WARN: set JWT_PRIVATE_KEY_PATH to enable dev login-select');

            return;
        }

        $r = $this->http->get($this->baseUrl . '/employees', ['headers' => $this->headers($mgr)]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $body = \is_array($body) ? $body : [];

        if ($r->getStatusCode() !== 200 || !isset($body['data']) || !\is_array($body['data'])) {
            $this->viewText($menu, 'Login', $this->statusLine($r->getStatusCode(), $body));

            return;
        }

        $employees = $body['data'];

        if (!$employees) {
            $this->viewText($menu, 'Login', 'FAIL [204] No employees');

            return;
        }

        $b = (new CliMenuBuilder())->setTitle('Select user')->setWidth($this->termWidth())->setMargin(1)->setPadding(1);

        foreach ($employees as $e) {
            if (!\is_array($e)) {
                continue;
            }
            $label = \sprintf('%s <%s> (%s)', $this->str($e['name'] ?? null), $this->str($e['email'] ?? null), $this->str($e['role_label'] ?? ($e['role'] ?? null)));
            $b->addItem($label, function (CliMenu $sm) use ($e, $menu) {
                $role = $e['role'] ?? 0;
                $tok = $this->issueDevJwt(
                    $this->str($e['id'] ?? null),
                    \is_numeric($role) ? (int) $role : 0,
                    3600,
                );

                if (!$tok) {
                    $this->viewText($menu, 'Login', 'FAIL [500] Cannot mint JWT');

                    return;
                }
                $this->saveSession([
                    'access_token' => $tok,
                    'employee_id' => $this->str($e['id'] ?? null),
                    'role' => \is_numeric($e['role'] ?? null) ? (int) $e['role'] : 0,
                ]);
                $this->viewText($menu, 'Login', 'SUCCESS [200] Logged in as ' . $this->str($e['email'] ?? null));
            });
        }
        $b->addItem('Back', new GoBackAction())->build()->open();
    }

    private function authRefresh(CliMenu $menu): void
    {
        $rid = $this->str($menu->askText()->setPromptText('refresh_id')->setValidationFailedText('required')->ask()->fetch());
        $r = $this->http->post($this->baseUrl . '/auth/refresh', ['json' => ['refresh_id' => $rid]]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $body = \is_array($body) ? $body : [];

        if ($r->getStatusCode() === 200 && isset($body['data']) && \is_array($body['data'])) {
            $session = $this->session();
            /** @var array<string,mixed> $merged */
            $merged = (\is_array($session) ? $session : []) + (\is_array($body['data']) ? $body['data'] : []);
            $this->saveSession($merged);
        }
        $this->viewText($menu, 'Refresh', $this->statusLine($r->getStatusCode(), $body, [200]));
    }

    private function authLogout(CliMenu $menu): void
    {
        $rid = $this->str($menu->askText()->setPromptText('refresh_id')->setValidationFailedText('required')->ask()->fetch());
        $r = $this->http->post($this->baseUrl . '/auth/logout', ['json' => ['refresh_id' => $rid]]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);

        if ($r->getStatusCode() === 200) {
            $this->saveSession([]);
        }
        $this->viewText($menu, 'Logout', $this->statusLine($r->getStatusCode(), \is_array($body) ? $body : [], [200]));
    }

    // ───────── actions (Employees) ─────────

    private function employeesList(CliMenu $menu): void
    {
        $r = $this->http->get($this->baseUrl . '/employees', ['headers' => $this->headers()]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $body = \is_array($body) ? $body : [];

        if ($r->getStatusCode() === 200 && isset($body['data']) && \is_array($body['data'])) {
            $rows = [];

            foreach ($body['data'] as $e) {
                if (!\is_array($e)) {
                    continue;
                }
                $rows[] = [
                    $this->str($e['id'] ?? null),
                    $this->str($e['name'] ?? null),
                    $this->str($e['email'] ?? null),
                    $this->str($e['employee_code'] ?? null),
                    $this->str($e['role_label'] ?? ($e['role'] ?? null)),
                ];
            }
            $tbl = $this->tableString(['ID', 'Name', 'Email', 'Code', 'Role'], $rows);
            $this->viewText($menu, 'Employees', $tbl . "\n\n" . $this->statusLine(200, []));

            return;
        }
        $this->viewText($menu, 'Employees', $this->statusLine($r->getStatusCode(), $body));
    }

    private function employeeShow(CliMenu $menu): void
    {
        $id = $this->str($menu->askText()->setPromptText('Employee UUID')->ask()->fetch());
        $r = $this->http->get($this->baseUrl . "/employees/$id", ['headers' => $this->headers()]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $body = \is_array($body) ? $body : [];

        if ($r->getStatusCode() === 200 && isset($body['data']) && \is_array($body['data'])) {
            $e = $body['data'];
            $tbl = $this->tableString(['Field', 'Value'], [
                ['id', $this->str($e['id'] ?? null)],
                ['name', $this->str($e['name'] ?? null)],
                ['email', $this->str($e['email'] ?? null)],
                ['employee_code', $this->str($e['employee_code'] ?? null)],
                ['role', $this->str($e['role_label'] ?? ($e['role'] ?? null))],
            ]);
            $this->viewText($menu, 'Employee', $tbl . "\n\n" . $this->statusLine(200, []));

            return;
        }
        $this->viewText($menu, 'Employee', $this->statusLine($r->getStatusCode(), $body));
    }

    private function employeeCreate(CliMenu $menu): void
    {
        $name = $this->str($menu->askText()->setPromptText('name')->setValidationFailedText('required')->ask()->fetch());
        $email = $this->str($menu->askText()->setPromptText('email')->setValidationFailedText('required')->ask()->fetch());
        $code = $this->str($menu->askText()->setPromptText('employee_code (7 digits)')->setValidationFailedText('required')->ask()->fetch());
        $role = (int) $menu->askText()->setPromptText('role (1|100)')->setPlaceholderText('1')->ask()->fetch();

        $r = $this->http->post($this->baseUrl . '/employees', [
            'headers' => $this->headers(),
            'json' => ['name' => $name, 'email' => $email, 'employee_code' => $code, 'role' => $role],
        ]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $this->viewText($menu, 'Create employee', $this->statusLine($r->getStatusCode(), \is_array($body) ? $body : [], [201]));
    }

    private function employeeUpdate(CliMenu $menu): void
    {
        $id = $this->str($menu->askText()->setPromptText('Employee UUID')->ask()->fetch());
        $name = $this->str($menu->askText()->setPromptText('name')->ask()->fetch());
        $email = $this->str($menu->askText()->setPromptText('email')->ask()->fetch());
        $code = $this->str($menu->askText()->setPromptText('employee_code (7 digits)')->ask()->fetch());
        $role = (int) $menu->askText()->setPromptText('role (1|100)')->setPlaceholderText('1')->ask()->fetch();

        $r = $this->http->patch($this->baseUrl . "/employees/$id", [
            'headers' => $this->headers(),
            'json' => ['name' => $name, 'email' => $email, 'employee_code' => $code, 'role' => $role],
        ]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $this->viewText($menu, 'Update employee', $this->statusLine($r->getStatusCode(), \is_array($body) ? $body : [], [200]));
    }

    private function employeeDelete(CliMenu $menu): void
    {
        $id = $this->str($menu->askText()->setPromptText('Employee UUID')->ask()->fetch());
        $r = $this->http->delete($this->baseUrl . "/employees/$id", ['headers' => $this->headers()]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $this->viewText($menu, 'Delete employee', $this->statusLine($r->getStatusCode(), \is_array($body) ? $body : [], [200, 204]));
    }

    // ───────── actions (Vacations) ─────────

    private function vacationSubmit(CliMenu $menu): void
    {
        $eid = $this->str($menu->askText()->setPromptText('Employee UUID')->ask()->fetch());
        $from = $this->str($menu->askText()->setPromptText('from (YYYY-MM-DD)')->ask()->fetch());
        $to = $this->str($menu->askText()->setPromptText('to (YYYY-MM-DD)')->ask()->fetch());
        $reason = $this->str($menu->askText()->setPromptText('reason')->setPlaceholderText('')->ask()->fetch());

        $r = $this->http->post($this->baseUrl . '/vacations', [
            'headers' => $this->headers(),
            'json' => ['employee_id' => $eid, 'from_date' => $from, 'to_date' => $to, 'reason' => $reason],
        ]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $this->viewText($menu, 'Submit vacation', $this->statusLine($r->getStatusCode(), \is_array($body) ? $body : [], [201]));
    }

    private function vacationsPending(CliMenu $menu): void
    {
        $r = $this->http->get($this->baseUrl . '/vacations/pending', ['headers' => $this->headers()]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $body = \is_array($body) ? $body : [];

        if ($r->getStatusCode() === 200 && isset($body['data']) && \is_array($body['data'])) {
            $rows = [];

            foreach ($body['data'] as $v) {
                if (!\is_array($v)) {
                    continue;
                }
                $rows[] = [
                    $this->str($v['id'] ?? null),
                    $this->str($v['employee_id'] ?? null),
                    $this->str($v['from'] ?? null),
                    $this->str($v['to'] ?? null),
                    $this->str($v['status'] ?? null),
                ];
            }
            $tbl = $this->tableString(['ID', 'Employee', 'From', 'To', 'Status'], $rows);
            $this->viewText($menu, 'Vacations pending', $tbl . "\n\n" . $this->statusLine(200, []));

            return;
        }
        $this->viewText($menu, 'Vacations pending', $this->statusLine($r->getStatusCode(), $body));
    }

    private function vacationsByEmployee(CliMenu $menu): void
    {
        $eid = $this->str($menu->askText()->setPromptText('Employee UUID')->ask()->fetch());
        $r = $this->http->get($this->baseUrl . "/vacations/employees/$eid/vacations", ['headers' => $this->headers()]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $body = \is_array($body) ? $body : [];

        if ($r->getStatusCode() === 200 && isset($body['data']) && \is_array($body['data'])) {
            $rows = [];

            foreach ($body['data'] as $v) {
                if (!\is_array($v)) {
                    continue;
                }
                $rows[] = [
                    $this->str($v['id'] ?? null),
                    $this->str($v['from'] ?? null),
                    $this->str($v['to'] ?? null),
                    $this->str($v['status'] ?? null),
                ];
            }
            $tbl = $this->tableString(['ID', 'From', 'To', 'Status'], $rows);
            $this->viewText($menu, 'Vacations by employee', $tbl . "\n\n" . $this->statusLine(200, []));

            return;
        }
        $this->viewText($menu, 'Vacations by employee', $this->statusLine($r->getStatusCode(), $body));
    }

    private function vacationApprove(CliMenu $menu): void
    {
        $id = $this->str($menu->askText()->setPromptText('Vacation UUID')->ask()->fetch());
        $r = $this->http->post($this->baseUrl . "/vacations/$id/approve", ['headers' => $this->headers()]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $this->viewText($menu, 'Approve vacation', $this->statusLine($r->getStatusCode(), \is_array($body) ? $body : [], [200]));
    }

    private function vacationReject(CliMenu $menu): void
    {
        $id = $this->str($menu->askText()->setPromptText('Vacation UUID')->ask()->fetch());
        $r = $this->http->post($this->baseUrl . "/vacations/$id/reject", ['headers' => $this->headers()]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $this->viewText($menu, 'Reject vacation', $this->statusLine($r->getStatusCode(), \is_array($body) ? $body : [], [200]));
    }

    private function vacationDeleteOwn(CliMenu $menu): void
    {
        $id = $this->str($menu->askText()->setPromptText('Vacation UUID')->ask()->fetch());
        $r = $this->http->delete($this->baseUrl . "/vacations/$id", ['headers' => $this->headers()]);
        /** @var array<string,mixed>|null $body */
        $body = \json_decode((string) $r->getBody(), true);
        $this->viewText($menu, 'Delete own vacation', $this->statusLine($r->getStatusCode(), \is_array($body) ? $body : [], [200, 204]));
    }
}
