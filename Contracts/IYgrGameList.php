<?php

namespace Providers\Ygr\Contracts;

interface IYgrGameList
{
    public function getArcadeGameList(): array;
    public function getFishGameList(): array;
}
