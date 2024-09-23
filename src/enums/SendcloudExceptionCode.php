<?php

namespace white\commerce\sendcloud\enums;

enum SendcloudExceptionCode: int
{
    case UNKNOWN = 0;
    case NO_ADDRESS_DATA = 1;
    case NOT_ALLOWED_TO_ANNOUNCE = 2;
    case UNAUTHORIZED = 3;
    case CONNECTION_FAILED = 4;
}
