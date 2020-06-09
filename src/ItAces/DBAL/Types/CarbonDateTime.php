<?php

namespace ItAces\DBAL\Types;

use Carbon\Carbon;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class CarbonTime extends DateTimeType
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
