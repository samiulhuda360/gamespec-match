<?php

namespace App\Ai;

use RuntimeException;

/** A model call failed or returned something unusable. Callers fall back to rules. */
class AiException extends RuntimeException
{
}
