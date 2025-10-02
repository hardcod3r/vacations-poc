<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Domain\Vacation\Entity\VacationRequest;
use Domain\Vacation\Enum\VacationStatus;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Persistence\Doctrine\Model\VacationRequestModel;

final class DoctrineVacationRequestRepository implements VacationRequestRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(VacationRequest $request): void
    {
        $m = $this->em->find(VacationRequestModel::class, $request->id())
            ?? new VacationRequestModel();

        $m = VacationRequestMapper::toModel($request, $m);
        $this->em->persist($m);
        $this->em->flush();
    }

    public function findById(string $id): ?VacationRequest
    {
        $m = $this->em->find(VacationRequestModel::class, $id);

        return $m ? VacationRequestMapper::toDomain($m) : null;
    }

    /**
     * @return VacationRequest[]
     */
    public function findPending(): array
    {
        $dql = 'SELECT m FROM ' . VacationRequestModel::class . ' m
                WHERE m.status = :s
                ORDER BY m.submittedAt DESC';

        $q = $this->em->createQuery($dql)->setParameter('s', VacationStatus::Pending->value);

        /** @var VacationRequestModel[] $rows */
        $rows = $q->getResult();

        return \array_map(
            static fn (VacationRequestModel $m): VacationRequest => VacationRequestMapper::toDomain($m),
            $rows,
        );
    }

    /**
     * @return VacationRequest[]
     */
    public function findByEmployee(string $employeeId): array
    {
        $dql = 'SELECT m FROM ' . VacationRequestModel::class . ' m
                WHERE m.employeeId = :eid
                ORDER BY m.submittedAt DESC';

        $q = $this->em->createQuery($dql)->setParameter('eid', $employeeId);

        /** @var VacationRequestModel[] $rows */
        $rows = $q->getResult();

        return \array_map(
            static fn (VacationRequestModel $m): VacationRequest => VacationRequestMapper::toDomain($m),
            $rows,
        );
    }

    public function delete(string $id): void
    {
        if ($m = $this->em->find(VacationRequestModel::class, $id)) {
            $this->em->remove($m);
            $this->em->flush();
        }
    }
}
