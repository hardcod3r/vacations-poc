<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Infrastructure\Persistence\Doctrine\Model\EmployeeModel;

final class DoctrineEmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(Employee $employee): void
    {
        $m = $this->em->find(EmployeeModel::class, $employee->id()) ?? new EmployeeModel();
        $this->em->persist(EmployeeMapper::toModel($employee, $m));
        $this->em->flush();
    }

    public function findById(string $id): ?Employee
    {
        $m = $this->em->find(EmployeeModel::class, $id);

        return $m ? EmployeeMapper::toDomain($m) : null;
    }

    public function findByEmail(string $email): ?Employee
    {
        // Με CITEXT στη ΒΔ, το = είναι case-insensitive.
        $m = $this->em->getRepository(EmployeeModel::class)->findOneBy([
            'email' => $email,
        ]);

        return $m ? EmployeeMapper::toDomain($m) : null;
    }

    /**
     * @return Employee[]
     */
    public function all(): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e')->from(EmployeeModel::class, 'e')
            ->orderBy('e.name', 'ASC');

        /** @var EmployeeModel[] $rows */
        $rows = $qb->getQuery()->getResult();

        return \array_map(
            static fn (EmployeeModel $m): Employee => EmployeeMapper::toDomain($m),
            $rows,
        );
    }

    public function delete(string $id): void
    {
        if ($m = $this->em->find(EmployeeModel::class, $id)) {
            $this->em->remove($m);
            $this->em->flush();
        }
    }
}
