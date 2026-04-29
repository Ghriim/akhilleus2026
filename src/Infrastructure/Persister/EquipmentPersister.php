<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\Equipment\EquipmentDataModel;
use App\Domain\Gateway\Persister\EquipmentPersisterGateway;
use App\Infrastructure\DataTransformer\StringDataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final class EquipmentPersister extends AbstractBaseMysqlPersister implements EquipmentPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private readonly StringDataTransformer $stringDataTransformer,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(EquipmentDataModel $model): EquipmentDataModel
    {
        $model->slug = $this->stringDataTransformer->slugify($model->label);
        $this->doCreate($model);

        return $model;
    }

    public function update(EquipmentDataModel $model): EquipmentDataModel
    {
        $model->slug = $this->stringDataTransformer->slugify($model->label);
        $this->doUpdate($model);

        return $model;
    }

    public function delete(EquipmentDataModel $model): void
    {
        $this->doDelete($model);
    }
}
