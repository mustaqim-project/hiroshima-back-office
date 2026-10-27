<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Collection;

class HoldBillPendingException extends Exception
{
    public function __construct(
        string $message = "Masih terdapat transaksi Hold Bill yang belum diselesaikan pada shift ini.",
        protected ?Collection $heldBills = null,
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getHeldBills(): Collection
    {
        return $this->heldBills ?? collect();
    }
}
