<?php

namespace VVK\DBAL\Types;

use Carbon\Carbon;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TimeType;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class CarbonTime extends TimeType
{

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $result = parent::convertToPHPValue($value, $platform);

        if ($result instanceof \DateTimeInterface) {
            return Carbon::instance($result);
        }

        return $result;
    }

}
