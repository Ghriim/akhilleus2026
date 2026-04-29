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

    public function create(DataModelInterface $model): DataModelInterface
    {
        $now = $this->clock->now();
        $model->createdAt = $now;
        $model->updatedAt = $now;
        $this->entityManager->persist($model);
        $this->entityManager->flush();
        $this->postCreate($model);

        return $model;
    }

    public function update(DataModelInterface $model): DataModelInterface
    {
        $model->updatedAt = $this->clock->now();
        $this->entityManager->flush();
        $this->postUpdate($model);

        return $model;
    }

    public function delete(DataModelInterface $model): void
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
