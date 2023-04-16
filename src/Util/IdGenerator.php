<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Util;

use RuntimeException;

/**
 * IdGenerator generates Ids which are unique during the runtime scope.
 *
 * @package Stomp\Util
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class IdGenerator
{
    private static array $generatedIds = [];

    /**
     * Generate a not used id.
     */
    public static function generateId() : int
    {
        if (count(self::$generatedIds) === PHP_INT_MAX) {
            throw new RuntimeException('Message Id generation failed.');
        }

        while ($rand = rand(1, PHP_INT_MAX)) {
            if (!in_array($rand, self::$generatedIds, true)) {
                self::$generatedIds[] = $rand;
                return $rand;
            }
        }
    }

    /**
     * Removes a previous generated id from currently used ids.
     */
    public static function releaseId(int $generatedId) : void
    {
        $index = array_search($generatedId, self::$generatedIds, true);
        if ($index !== false) {
            unset(self::$generatedIds[$index]);
        }
    }
}
