<?php

namespace white\commerce\sendcloud\services;

use craft\base\Component;
use white\commerce\sendcloud\models\Integration;
use white\commerce\sendcloud\records\Integration as IntegrationRecord;
use yii\base\InvalidArgumentException;

class Integrations extends Component
{
    /**
     * @param int $id
     * @return Integration|null
     */
    public function getIntegrationById(int $id)
    {
        $record = IntegrationRecord::findOne(['id' => $id]);
        
        return $record != null ? new Integration($record) : null;
    }

    /**
     * @param int $siteId
     * @return Integration|null
     */
    public function getIntegrationBySiteId(int $siteId)
    {
        $record = IntegrationRecord::findOne(['siteId' => $siteId]);

        return $record != null ? new Integration($record) : null;
    }

    /**
     * @return Integration[]|array
     */
    public function getAllIntegrations(): array
    {
        $result = [];
        foreach (IntegrationRecord::find()->all() as $record) {
            $result[] = new Integration($record);
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
        if ($model->id) {
            $record = IntegrationRecord::findOne($model->id);
            if (!$record) {
                throw new InvalidArgumentException('No integration exists with the ID “{id}”', ['id' => $model->id]);
            }
        } else {
            $record = new IntegrationRecord();
        }

        if ($runValidation && !$model->validate()) {
            return false;
        }
        
        $record->siteId = $model->siteId;
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
        $model->id = $record->id;
        
        return true;
    }

    /**
     * @param int $id
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteIntegrationById(int $id): bool
    {
        $record = IntegrationRecord::findOne($id);
        if (!$record) {
            return false;
        }

        return (bool)$record->delete();
    }
}
