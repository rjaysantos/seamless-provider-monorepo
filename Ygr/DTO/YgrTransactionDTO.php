<?php

namespace Providers\Ygr\DTO;

use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class YgrTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+8';
}
