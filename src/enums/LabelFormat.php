<?php

namespace white\commerce\sendcloud\enums;

use Craft;

enum LabelFormat: string
{
    case FORMAT_A4 = 'A4';
    case FORMAT_A5 = 'A5';
    case FORMAT_A6 = 'A6';

    // Legacy aliases for backward compatibility with existing config values.
    public const FORMAT_A4_TOP_LEFT = self::FORMAT_A4;
    public const FORMAT_A4_TOP_RIGHT = self::FORMAT_A4;
    public const FORMAT_A4_BOTTOM_LEFT = self::FORMAT_A4;
    public const FORMAT_A4_BOTTOM_RIGHT = self::FORMAT_A4;

    public function getLabel(): string
    {
        return match ($this) {
            self::FORMAT_A4 => Craft::t('commerce-sendcloud', 'A4 format'),
            self::FORMAT_A5 => Craft::t('commerce-sendcloud', 'A5 format'),
            self::FORMAT_A6 => Craft::t('commerce-sendcloud', 'A6 format, for label printers'),
        };
    }

    public static function fromSettingValue(int|string $value): self
    {
        return match ($value) {
            0, 1, 2, 3, self::FORMAT_A4->value => self::FORMAT_A4,
            self::FORMAT_A5->value => self::FORMAT_A5,
            default => self::FORMAT_A6,
        };
    }

    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $labelFormat) {
            $options[] = [
                'value' => $labelFormat->value,
                'label' => $labelFormat->getLabel(),
            ];
        }
        return $options;
    }
}
