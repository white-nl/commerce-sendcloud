<?php

namespace white\commerce\sendcloud\enums;

enum ShipmentType: int
{
    case Gift = 0;

    case Documents = 1;

    case CommercialGoods = 2;

    case CommercialSample = 3;

    case ReturnedGoods = 4;
}