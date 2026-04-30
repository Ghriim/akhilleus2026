<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training\Muscle;

use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Infrastructure\DataTransformer\StringDataTransformer;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class MusclePersister extends AbstractBaseMysqlPersister implements MusclePersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private StringDataTransformer $stringDataTransformer,
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
