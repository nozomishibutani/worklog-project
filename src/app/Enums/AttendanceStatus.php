<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case DRAFT = 'draft';
    case COMPLETED = 'completed';
    case PENDING = 'pending';
    case APPROVED = 'approved';
}
