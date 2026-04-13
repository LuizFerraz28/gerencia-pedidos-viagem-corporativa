<?php

namespace App\Domain\TravelOrder\Exceptions;

use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;
use DomainException;

class TravelOrderException extends DomainException
{
    public static function ownerCannotChangeStatus(): self
    {
        return new self('O solicitante do pedido não pode alterar o status do próprio pedido.');
    }

    public static function invalidStatusTransition(
        TravelOrderStatus $from,
        TravelOrderStatus $to
    ): self {
        return new self(
            "Não é possível alterar o status de '{$from->label()}' para '{$to->label()}'."
        );
    }

    public static function cannotCancelAfterApproval(): self
    {
        return new self('Não é possível cancelar um pedido que já foi aprovado.');
    }

    public static function notFound(int $id): self
    {
        return new self("Pedido de viagem #{$id} não encontrado.");
    }
}
