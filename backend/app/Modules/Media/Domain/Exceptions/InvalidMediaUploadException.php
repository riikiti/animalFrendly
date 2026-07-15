<?php

declare(strict_types=1);

namespace App\Modules\Media\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class InvalidMediaUploadException extends DomainException
{
    public static function unsupportedType(string $mimeType): self
    {
        return new self("Неподдерживаемый тип файла «{$mimeType}».");
    }

    public static function tooLarge(int $maxSizeKb): self
    {
        return new self("Файл слишком большой, максимум {$maxSizeKb} КБ.");
    }
}
