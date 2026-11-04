<?php

namespace App\Services;

use RuntimeException;

/** RD2-R3 Phase 2: a repair cannot start or be accepted because the shop is at its simultaneous-repair limit. */
class RepairCapacityException extends RuntimeException
{
}
