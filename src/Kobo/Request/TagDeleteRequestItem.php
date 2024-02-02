<?php

namespace App\Kobo\Request;

class TagDeleteRequestItem
{
    public const TYPE_REVISION_TAG_ITEM = 'ProductRevisionTagItem';

    public ?string $revisionId = null;
    public ?string $type = self::TYPE_REVISION_TAG_ITEM;
}
