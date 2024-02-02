<?php

namespace App\Kobo\Request;

use App\Entity\Book;

class TagDeleteRequest
{
    /** @var array<int, TagDeleteRequestItem> */
    public array $items;

    public function hasItem(Book $book): bool
    {
        foreach ($this->items as $item) {
            if ($item->revisionId === $book->getUuid()
                && $item->type === TagDeleteRequestItem::TYPE_REVISION_TAG_ITEM) {
                return true;
            }
        }

        return false;
    }
}
