<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\Muscle\MuscleDataModel;
use App\Domain\Gateway\Persister\MusclePersisterGateway;
use App\Infrastructure\DataTransformer\StringDataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final class MusclePersister extends AbstractBaseMysqlPersister implements MusclePersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private readonly StringDataTransformer $stringDataTransformer,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(MuscleDataModel $model): MuscleDataModel
    {
        $model->slug = $this->stringDataTransformer->slugify($model->label);
        $this->doCreate($model);

        return $model;
    }

    public function update(MuscleDataModel $model): MuscleDataModel
    {
        $model->slug = $this->stringDataTransformer->slugify($model->label);
        $this->doUpdate($model);

        return $model;
    }

    public function delete(MuscleDataModel $model): void
    {
        $this->doDelete($model);
    }
}
