<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

abstract class AbstractBaseMysqlPersister
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ClockInterface $clock,
    ) {
    }

    protected function doCreate(DataModelInterface $model): void
    {
        $now = $this->clock->now();
        $model->createdAt = $now;
        $model->updatedAt = $now;
        $this->entityManager->persist($model);
        $this->entityManager->flush();
        $this->postCreate($model);
    }

    protected function doUpdate(DataModelInterface $model): void
    {
        $model->updatedAt = $this->clock->now();
        $this->entityManager->flush();
        $this->postUpdate($model);
    }

    protected function doDelete(DataModelInterface $model): void
    {
        $this->entityManager->remove($model);
        $this->entityManager->flush();
        $this->postDelete($model);
    }

    protected function postCreate(DataModelInterface $model): void
    {
    }

    protected function postUpdate(DataModelInterface $model): void
    {
    }

    protected function postDelete(DataModelInterface $model): void
    {
    }
}
