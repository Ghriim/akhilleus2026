<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training\Movement;

use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Infrastructure\DataTransformer\StringDataTransformer;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * @extends AbstractBaseMysqlPersister<MovementDataModel>
 */
final readonly class MovementPersister extends AbstractBaseMysqlPersister implements MovementPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private StringDataTransformer $stringDataTransformer,
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
