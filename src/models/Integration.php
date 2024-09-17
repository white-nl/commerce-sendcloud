<?php


namespace white\commerce\sendcloud\models;

use craft\base\Model;
use DateTime;

/**
 *
 * @property-read bool $isActive
 */
class Integration extends Model
{
    /** @var int */
    public int $id;

    /** @var integer */
    public int $storeId;

    /** @var string */
    public string $token;

    /** @var integer|null */
    public ?int $externalId = null;

    /** @var string */
    public string $publicKey = '';

    /** @var string */
    public string $secretKey = '';

    /** @var string */
    public string $system = '';

    /** @var string */
    public string $shopUrl = '';

    /** @var string */
    public string $webhookUrl = '';

    /** @var boolean */
    public bool $servicePointEnabled = false;

    /** @var array */
    public array $servicePointCarriers = [];

    public DateTime $dateCreated;

    public DateTime $dateUpdated;

    public string $uid;

    public function rules(): array
    {
        return [
            [['storeId', 'token'], 'required'],
            [['storeId', 'externalId'], 'integer'],
            [['token', 'publicKey', 'secretKey', 'system', 'shopUrl', 'webhookUrl'], 'string'],
        ];
    }

    public function getIsActive(): bool
    {
        return !empty($this->externalId) && !empty($this->publicKey) && !empty($this->secretKey);
    }
}
