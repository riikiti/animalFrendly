<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Enums;

/**
 * Многозначные пользовательские теги «для чего ищу общение» — независимы от PetPurpose
 * (одиночный системный routing-флаг, часть значений которого выставляется не пользователем,
 * а модулями Marketplace/Shelter). Пересечение с purpose=breeding намеренное: там — флаг для
 * бизнес-логики, здесь — чисто описательный тег.
 */
enum PetSocialTag: string
{
    case Walks = 'walks';
    case Friendship = 'friendship';
    case Mating = 'mating';
    case Training = 'training';
    case PetSitting = 'pet_sitting';
}
