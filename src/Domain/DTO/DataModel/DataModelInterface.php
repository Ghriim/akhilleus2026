<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

interface DataModelInterface
{
    public string $id { get; set; }
    public \DateTimeImmutable $createdAt { get; set; }
    public \DateTimeImmutable $updatedAt { get; set; }
}
