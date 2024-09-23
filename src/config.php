<?php

/**
 * @copyright Copyright (c) WHITE Digital Agency
 */

/**
 * Sendcloud config.php
 *
 * This file exist only as a template for the Sendcloud settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'commerce-sendcloud.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    '*' => [
        // When set to True configured shipping rules will be applied before creating the label and announcing the Parcel
        //'applyShippingRules' => true,

        // Select the format in which the shipping labels should be printed.
        //'labelFormat' => white\commerce\sendcloud\enums\LabelFormat\LabelFormat::FORMAT_A6

        // Select the Craft Commerce product field containing the HS product codes. HS codes are required for shipping outside the EU.
        //'hsCodeFieldHandle' => null,

        // Select the Craft Commerce product field containing the country of Origin. Use only ISO2 country codes!
        // 'originCountryFieldHandle' => null,

        // Select the Craft field linked to the Address element containing the phone number
        //'phoneNumberFieldHandle' => null,

        // The priority to give the push order job (the lower the number, the higher the priority). Set to `null` to inherit the default priority.
        // 'pushOrderJobPriority' => 1024,

        // The priority to give the create label job (the lower the number, the higher the priority). Set to `null` to inherit the default priority.
        // 'createLabelJobPriority' => 1024,
    ],
];
