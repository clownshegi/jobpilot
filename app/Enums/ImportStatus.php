<?php

namespace App\Enums;

enum ImportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Done = 'done';
    case Failed = 'failed';

    public function isFinished(): bool
    {
        return $this === self::Done || $this === self::Failed;
    }
}
