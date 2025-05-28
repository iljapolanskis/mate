<?php

namespace App\Playground\Api;

use App\Playground\Model\WriteModel;

interface WriteRepositoryInterface
{
    public function getById(int $id): WriteModel;
}
