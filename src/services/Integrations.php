<?php

namespace white\commerce\sendcloud\services;

use craft\base\Component;
use white\commerce\sendcloud\models\Integration;
use white\commerce\sendcloud\records\Integration as IntegrationRecord;
use yii\base\InvalidArgumentException;
use yii\db\BaseActiveRecord;
use yii\db\StaleObjectException;

/**
 *
 * @property-read Integration[] $allIntegrations
 */
class Integrations extends Component
{
    /**
     * @param int $id
     * @return Integration|null
     */
    public function getIntegrationById(int $id): ?Integration
    {
        $record = IntegrationRecord::findOne(['id' => $id]);
        
        return $record != null ? new Integration($record->toArray()) : null;
    }

    /**
     * @param int $siteId
     * @return Integration|null
     */
    public function getIntegrationByStoreId(int $storeId): ?Integration
    {
        $record = IntegrationRecord::findOne(['storeId' => $storeId]);

        return $record != null ? new Integration($record->toArray()) : null;
    }

    /**
     * @return Integration[]|array
     */
    public function getAllIntegrations(): array
    {
        $result = [];
        /** @var BaseActiveRecord $record */
        foreach (IntegrationRecord::find()->all() as $record) {
            $result[] = new Integration($record->toArray());
        }
        
        return $result;
    }

    /**
     * @param Integration $model
     * @param bool $runValidation
     * @return bool
     */
    public function saveIntegration(Integration $model, bool $runValidation = true): bool
    {
        if (isset($model->id)) {
            /** @var IntegrationRecord $record */
            $record = IntegrationRecord::findOne($model->id);
            if ($record == null) {
                throw new InvalidArgumentException('No integration exists with the ID “' . $model->id . '”');
            }
        } else {
            $record = new IntegrationRecord();
        }

        if ($runValidation && !$model->validate()) {
            return false;
        }

        $record->storeId = $model->storeId;
        $record->token = $model->token;
        $record->externalId = $model->externalId;
        $record->publicKey = $model->publicKey;
        $record->secretKey = $model->secretKey;
        $record->system = $model->system;
        $record->shopUrl = $model->shopUrl;
        $record->webhookUrl = $model->webhookUrl;
        $record->servicePointEnabled = $model->servicePointEnabled;
        $record->servicePointCarriers = $model->servicePointCarriers;

        $record->save(false);
        $model->id = $record->getAttribute('id');
        
        return true;
    }

    /**
     * @param int $id
     * @return bool
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function deleteIntegrationById(int $id): bool
    {
        $record = IntegrationRecord::findOne($id);
        if (!$record instanceof IntegrationRecord) {
            return false;
        }

        return (bool)$record->delete();
    }
}
