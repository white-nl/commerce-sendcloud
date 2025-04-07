<?php

namespace white\commerce\sendcloud\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;
use white\commerce\sendcloud\enums\LabelFormat;

/**
 *
 * @property-read array $availableTextFields
 */
class Settings extends Model
{
    /**
     * @var bool Wheter the build in inventory item codes should be used for the Harmonized System Code and the Country Code of Origin
     */
    public bool $useInventoryItemCodes = true;

    /**
     * @var string|null Select the Craft Commerce product field containing the HS product codes. HS codes are required for shipping outside the EU.
     */
    public ?string $hsCodeFieldHandle = null;

    /**
     * @var string|null Select the Craft Commerce product field containing the country of Origin. Use only ISO2 country codes!
     */
    public ?string $originCountryFieldHandle = null;

    /**
     * @var string|null Select the Craft field linked to the Address element containing the phone number
     */
    public ?string $phoneNumberFieldHandle = null;

    /**
     * @var int The priority to give the push order job (the lower the number, the higher the priority). Set to `null` to inherit the default priority.
     *
     * @since 2.2.0
     */
    public int $pushOrderJobPriority = 1024;

    /**
     * @var int The priority to give the create label job (the lower the number, the higher the priority). Set to `null` to inherit the default priority.
     *
     * @since 2.2.0
     */
    public int $createLabelJobPriority = 1024;

    /**
     * @var int The format of the shipping label to be downloaded
     *
     * @sine 4.0.0
     */
    public int $labelFormat = LabelFormat::FORMAT_A6->value;

    /**
     * @var bool|string Whether to apply shipping rules provided in Sendcloud to match shipping methods
     *
     * @since 4.0.0
     */
    public bool|string $applyShippingRules = true;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
    }

    /**
     * @return array
     */
    public function getAvailableTextFields(): array
    {
        $options = [
            ['label' => null, 'value' => null],
        ];
        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            $options[] = ['label' => $field->name, 'value' => $field->handle];
        }

        return $options;
    }

    public function isApplyShippingRules(bool $parse = true): bool|string
    {
        return $parse ? App::parseBooleanEnv($this->applyShippingRules) : $this->applyShippingRules;
    }

    public function setApplyShippingRules(bool|string $applyShippingRules): void
    {
        $this->applyShippingRules = $applyShippingRules;
    }

    public function getLabelFormat(): LabelFormat
    {
        return LabelFormat::from($this->labelFormat);
    }
}
