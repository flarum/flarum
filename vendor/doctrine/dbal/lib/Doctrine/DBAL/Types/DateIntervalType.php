<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Type that maps interval string to a PHP DateInterval Object.
 */
class DateIntervalType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Type::DATEINTERVAL;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $fieldDeclaration['length'] = 255;
        $fieldDeclaration['fixed']  = true;

        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof \DateInterval) {
            return $value->format('P%YY%MM%DDT%HH%IM%SS');
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', 'DateInterval']);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof \DateInterval) {
            return $value;
        }

        try {
            return new \DateInterval($value);
        } catch (\Exception $exception) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), 'P%YY%MM%DDT%HH%IM%SS', $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
