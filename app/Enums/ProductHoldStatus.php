<?php

namespace App\Enums;

enum ProductHoldStatus: string
{
    case Requested = 'requested';
    case Ready = 'ready';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
