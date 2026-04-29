<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\Movement\MovementDataModel;
use App\Domain\Gateway\Persister\MovementPersisterGateway;
use App\Infrastructure\DataTransformer\StringDataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final class MovementPersister extends AbstractBaseMysqlPersister implements MovementPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private readonly StringDataTransformer $stringDataTransformer,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(MovementDataModel $model): MovementDataModel
    {
        $model->slug = $this->stringDataTransformer->slugify($model->label);
        $this->doCreate($model);

        return $model;
    }

    public function update(MovementDataModel $model): MovementDataModel
    {
        $model->slug = $this->stringDataTransformer->slugify($model->label);
        $this->doUpdate($model);

        return $model;
    }

    public function delete(MovementDataModel $model): void
    {
        $this->doDelete($model);
    }
}
