<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine\Auth;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Infrastructure\Persistence\Doctrine\Model\RefreshTokenModel;

final class RefreshTokenRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function create(RefreshTokenModel $m): void
    {
        $this->em->persist($m);
        $this->em->flush();
    }

    public function find(string $id): ?RefreshTokenModel
    {
        return $this->em->find(RefreshTokenModel::class, $id);
    }

    public function revoke(string $id): void
    {
        if ($m = $this->find($id)) {
            $m->revokedAt = new \DateTimeImmutable('now');
            $this->em->flush();
        }
    }

    public function rotate(string $oldId, string $newId): void
    {
        if ($m = $this->find($oldId)) {
            $m->rotatedTo = $newId;
            $m->revokedAt = new \DateTimeImmutable('now');
            $this->em->flush();
        }
    }

    /**
     * @return RefreshTokenModel[]
     */
    public function activeByEmployee(string $employeeId): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('t')->from(RefreshTokenModel::class, 't')
            ->where('t.employeeId = :eid')
            ->andWhere('t.expiresAt > :now')
            ->andWhere('t.revokedAt IS NULL')
            ->setParameters(new ArrayCollection([
                new Parameter('eid', $employeeId),
                new Parameter('now', new \DateTimeImmutable('now')),
            ]))
            ->orderBy('t.issuedAt', 'DESC');

        /** @var RefreshTokenModel[] $rows */
        $rows = $qb->getQuery()->getResult();

        return $rows;
    }
}
