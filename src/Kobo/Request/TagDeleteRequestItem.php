<?php

namespace App\Kobo\Request;

class TagDeleteRequestItem
{
    public function __construct(?string $revisionId = null)
    {
        if ($revisionId !== null) {
            $this->setRevisionId($revisionId);
        }
    }

    public const TYPE_REVISION_TAG_ITEM = 'ProductRevisionTagItem';

    public ?string $revisionId = null;
    public ?string $type = self::TYPE_REVISION_TAG_ITEM;

    public function setRevisionId(?string $revisionId): self
    {
        $this->revisionId = $revisionId;

        return $this;
    }
}
