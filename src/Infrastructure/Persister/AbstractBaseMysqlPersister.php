<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

/**
 * @template T of DataModelInterface
 */
abstract readonly class AbstractBaseMysqlPersister
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ClockInterface $clock,
    ) {
    }

    /**
     * @param T $model
     *
     * @return T
     */
    protected function doCreate(DataModelInterface $model): DataModelInterface
    {
        $model->id = (string) new Ulid();

        $now = $this->clock->now();
        $model->createdAt = $now;
        $model->updatedAt = $now;

        $this->entityManager->persist($model);
        $this->entityManager->flush();
        $this->postCreate($model);

        return $model;
    }

    /**
     * @param T $model
     *
     * @return T
     */
    protected function doUpdate(DataModelInterface $model): DataModelInterface
    {
        $model->updatedAt = $this->clock->now();
        $this->entityManager->flush();
        $this->postUpdate($model);

        return $model;
    }

    /**
     * @param T $model
     */
    protected function doDelete(DataModelInterface $model): void
    {
        $this->entityManager->remove($model);
        $this->entityManager->flush();
        $this->postDelete($model);
    }

    /**
     * @param T $model
     */
    protected function postCreate(DataModelInterface $model): void
    {
    }

    /**
     * @param T $model
     */
    protected function postUpdate(DataModelInterface $model): void
    {
    }

    /**
     * @param T $model
     */
    protected function postDelete(DataModelInterface $model): void
    {
    }
}
