<?php

namespace white\commerce\sendcloud\enums;

enum ExportType: string
{
    case Private = "private";

    case CommercialB2c = "commercial_b2c";

    case CommercialB2b = "commercial_b2b";
}
