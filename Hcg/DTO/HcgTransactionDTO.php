<?php

namespace Providers\Hcg\DTO;

use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class HcgTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+8';
}