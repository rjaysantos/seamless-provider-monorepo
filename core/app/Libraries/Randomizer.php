<?php

namespace App\Libraries;

use Illuminate\Support\Str;
use App\Contracts\IRandomizer;

class Randomizer
{
    public function createToken(): string
    {
        return Str::uuid()->toString();
    }
}
