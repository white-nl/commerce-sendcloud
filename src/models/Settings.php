<?php

namespace white\commerce\sendcloud\models;

use Craft;
use craft\base\Model;
use craft\commerce\Plugin as CommercePlugin;
use yii\base\InvalidConfigException;

/**
 *
 * @property-read array $availableTextFields
 */
class Settings extends Model
{
    /**
     * @var string The plugin name as you'd like it to be displayed in the Control Panel.
     */
    public string $pluginNameOverride = '';

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
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
    }

    public function rules(): array
    {
        return [
            ['pluginNameOverride', 'default', 'value' => Craft::t('commerce-sendcloud', "Sendcloud")],
        ];
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
}
