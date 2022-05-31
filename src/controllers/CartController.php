<?php


namespace white\commerce\sendcloud\controllers;

use Craft;
use craft\commerce\Plugin as CommercePlugin;
use craft\web\Controller;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\helpers\VarDumper;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class CartController extends Controller
{
    protected array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = true;

    /**
     * @return bool[]|\craft\web\Response|false[]
     * @throws BadRequestHttpException
     */
    public function actionSetServicePoint(): \craft\web\Response|array
    {
        // Add CORS headers
        $headers = $this->response->getHeaders();
        $headers->setDefault('Access-Control-Allow-Credentials', 'true');
        $headers->setDefault('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-CSRF-Token');
        $headers->setDefault('Access-Control-Allow-Origin', '*');

        if ($this->request->getIsOptions()) {
            // This is just a preflight request, no need to run the actual query yet
            $this->response->format = Response::FORMAT_RAW;
            $this->response->data = '';
            return $this->response;
        }

        $this->response->format = Response::FORMAT_JSON;
        $orderNumber = Craft::$app->request->getRequiredBodyParam('orderNumber');
        $servicePointParams = Craft::$app->request->getRequiredBodyParam('servicePoint');
        
        try {
            $cart = CommercePlugin::getInstance()->getOrders()->getOrderByNumber($orderNumber);
            if ($cart->isCompleted) {
                throw new BadRequestHttpException('Cart is already completed: ' . $orderNumber);
            }
            
            $status = SendcloudPlugin::getInstance()->orderSync->getOrCreateOrderSyncStatus($cart);
            $status->servicePoint = $servicePointParams;
            if (!SendcloudPlugin::getInstance()->orderSync->saveOrderSyncStatus($status)) {
                throw new \RuntimeException('SendCloud order not save:' . VarDumper::dumpAsString($status->errors));
            }
            return [
                'success' => true,
            ];
        } catch (\Exception) {
            return [
                'success' => false,
            ];
        }
    }
}
