<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training\Equipment;

use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;
use App\Domain\Gateway\Persister\Training\Equipment\EquipmentPersisterGateway;
use App\Infrastructure\DataTransformer\StringDataTransformer;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class EquipmentPersister extends AbstractBaseMysqlPersister implements EquipmentPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private StringDataTransformer $stringDataTransformer,
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
