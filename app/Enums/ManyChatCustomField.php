<?php

namespace App\Enums;

enum ManyChatCustomField: string
{
    case USER_ID = '12123703';
    case REGION = '12123704';

    public function getId(): string
    {
        return $this->value;
    }
}
