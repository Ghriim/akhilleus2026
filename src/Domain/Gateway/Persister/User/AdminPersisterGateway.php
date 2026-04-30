<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\User;

use App\Domain\DTO\DataInput\User\RegisterAdminDataInput;
use App\Domain\DTO\DataModel\User\AdminDataModel;

interface AdminPersisterGateway
{
    public function create(RegisterAdminDataInput $input): AdminDataModel;

    public function update(AdminDataModel $model): AdminDataModel;

    public function delete(AdminDataModel $model): void;
}
