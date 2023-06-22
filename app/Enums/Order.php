<?php

namespace App\Enums;

enum Order: string
{
    case Oldest = 'oldest';
    case Newest = 'newest';
}
