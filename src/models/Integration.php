<?php


namespace white\commerce\sendcloud\models;

use craft\base\Model;

class Integration extends Model
{
    /** @var integer */
    public $id;

    /** @var integer */
    public $siteId;

    /** @var string */
    public $token;

    /** @var integer */
    public $externalId;

    /** @var string */
    public $publicKey;

    /** @var string */
    public $secretKey;

    /** @var string */
    public $system;

    /** @var string */
    public $shopUrl;

    /** @var string */
    public $webhookUrl;

    /** @var boolean */
    public $servicePointEnabled = false;
    
    /** @var array */
    public $servicePointCarriers = [];
    
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    public function rules()
    {
        return [
            [['siteId', 'token'], 'required'],
            [['siteId', 'externalId'], 'integer'],
            [['token', 'publicKey', 'secretKey', 'system', 'shopUrl', 'webhookUrl'], 'string'],
        ];
    }

    public function getIsActive()
    {
        return !empty($this->externalId) && !empty($this->publicKey) && !empty($this->secretKey);
    }
}
