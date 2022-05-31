<?php

declare(strict_types=1);

use craft\ecs\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function(ECSConfig $ECSConfig): void {
    $ECSConfig->parameters();
    $ECSConfig->parallel();
    $ECSConfig->sets([SetList::CRAFT_CMS_4]);
    $ECSConfig->paths([
        __DIR__ . '/src',
        __FILE__,
    ]);
};
