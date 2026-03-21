<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
}
