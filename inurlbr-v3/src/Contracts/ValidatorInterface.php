<?php

declare(strict_types=1);

namespace Inurlbr\Contracts;

use InurlBr\Models\Vulnerability;

/**
 * Interface para validadores de vulnerabilidades.
 */
interface ValidatorInterface
{
    /**
     * Valida si una URL es vulnerable.
     *
     * @param string $url La URL a validar.
     * @return Vulnerability|null Retorna un objeto Vulnerability si se encuentra una, null en caso contrario.
     */
    public function validate(string $url): ?Vulnerability;

    /**
     * Retorna el nombre del tipo de vulnerabilidad que valida este validator.
     *
     * @return string
     */
    public function getType(): string;
}
