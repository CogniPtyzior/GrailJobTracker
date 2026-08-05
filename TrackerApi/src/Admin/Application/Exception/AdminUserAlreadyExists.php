<?php

namespace App\Admin\Application\Exception;

/**
 * Raised when an admin user creation would violate the normalized email uniqueness rule.
 */
final class AdminUserAlreadyExists extends \RuntimeException
{
}