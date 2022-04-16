<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Exception;

use Throwable;

class HydrationException extends ODMException
{
    public function __construct(string $message, int $code = 11000, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
