<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An expected import failure; the message is shown to the user as is.
 */
class VacancyImportException extends RuntimeException {}
