<?php

namespace App\Enums;

enum Type: string
{
    case DAILY = 'daily';
    case MONTHLY = 'monthly';
    case PERSONALLY = 'personally';
}

