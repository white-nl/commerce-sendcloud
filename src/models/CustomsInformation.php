<?php

namespace white\commerce\sendcloud\models;

use white\commerce\sendcloud\enums\ExportType;

class CustomsInformation
{
    public function __construct(
        protected string $invoiceNumber,
        protected string $exportReason,
        protected ExportType $exportType,
    ) {
    }
}
