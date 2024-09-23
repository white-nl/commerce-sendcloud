<?php

namespace white\commerce\sendcloud\enums;

use Craft;

enum LabelFormat: int
{
    case FORMAT_A4_TOP_LEFT = 0;
    case FORMAT_A4_TOP_RIGHT = 1;
    case FORMAT_A4_BOTTOM_LEFT = 2;
    case FORMAT_A4_BOTTOM_RIGHT = 3;
    case FORMAT_A6 = 4;

    public function getUrl(array $data): ?string
    {
        if (isset($data['label'])) {
            if ($this === self::FORMAT_A6) {
                return $data['label']['label_printer'] ?? null;
            }
            return $data['label']['normal_printer'][$this->value] ?? null;
        }

        return null;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::FORMAT_A4_TOP_LEFT => Craft::t('commerce-sendcloud', 'A4 format, label on top left'),
            self::FORMAT_A4_TOP_RIGHT => Craft::t('commerce-sendcloud', 'A4 format, label on top right'),
            self::FORMAT_A4_BOTTOM_LEFT => Craft::t('commerce-sendcloud', 'A4 format, label on bottom left'),
            self::FORMAT_A4_BOTTOM_RIGHT => Craft::t('commerce-sendcloud', 'A4 format, label on bottom right'),
            self::FORMAT_A6 => Craft::t('commerce-sendcloud', 'A6 format, for label printers'),
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
