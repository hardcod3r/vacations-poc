<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine\Model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
final class RefreshTokenModel
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $id; // jti

    #[ORM\Column(name: 'employee_id', type: 'string', length: 36)]
    public string $employeeId;

    #[ORM\Column(name: 'issued_at', type: 'datetimetz_immutable')]
    public \DateTimeImmutable $issuedAt;

    #[ORM\Column(name: 'expires_at', type: 'datetimetz_immutable')]
    public \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'revoked_at', type: 'datetimetz_immutable', nullable: true)]
    public ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(name: 'rotated_to', type: 'string', length: 36, nullable: true)]
    public ?string $rotatedTo = null;
}
