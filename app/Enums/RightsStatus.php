<?php

namespace App\Enums;

enum RightsStatus: string
{
    case Unknown = 'unknown';
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Restricted = 'restricted';
    case Removed = 'removed';
}
