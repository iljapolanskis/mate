<?php

namespace App\Playground\Api;

use App\Playground\Model\ReadModel;

interface ReadRepositoryInterface
{
    public function getById(int $id): ReadModel;
}
