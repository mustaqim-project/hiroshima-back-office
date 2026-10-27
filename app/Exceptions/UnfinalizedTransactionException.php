<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Collection;

class UnfinalizedTransactionException extends Exception
{
    public function __construct(
        string $message = "Masih terdapat transaksi atau pembayaran yang belum selesai diproses pada shift ini.",
        protected ?Collection $orders = null,
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getOrders(): Collection
    {
        return $this->orders ?? collect();
    }
}
