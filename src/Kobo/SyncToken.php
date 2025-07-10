<?php

namespace App\Kobo;

use App\Kobo\SyncToken\SyncTokenV1;

@trigger_deprecation('kobo', '0', 'Usage of SyncToken is deprecated, use SyncTokenInterface instead');

/**
 * @deprecated Use SyncTokenInterface instead
 */
class SyncToken extends SyncTokenV1
{
}
