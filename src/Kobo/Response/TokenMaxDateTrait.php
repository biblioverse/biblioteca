<?php

namespace App\Kobo\Response;

use App\Kobo\SyncToken\SyncTokenInterface;

trait TokenMaxDateTrait
{
    protected function maxLastCreated(SyncTokenInterface $token, ?\DateTimeImmutable ...$value): ?\DateTimeImmutable
    {
        return self::max(
            $token->getLastModified(),
            ...$value
        );
    }

    protected function maxLastModified(SyncTokenInterface $token, ?\DateTimeImmutable ...$value): ?\DateTimeImmutable
    {
        return self::max(
            $token->getLastModified(),
            ...$value
        );
    }

    protected static function max(?\DateTimeImmutable ...$dates): ?\DateTimeImmutable
    {
        $max = null;
        foreach ($dates as $date) {
            if (!$date instanceof \DateTimeImmutable) {
                continue;
            }

            if (!$max instanceof \DateTimeImmutable || $date > $max) {
                $max = $date;
            }
        }

        return $max;
    }
}
