<?php

namespace white\commerce\sendcloud\enums;

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
}