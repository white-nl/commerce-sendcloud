<?php

namespace white\commerce\sendcloud\exception;

use craft\helpers\Json;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use white\commerce\sendcloud\enums\SendcloudExceptionCode;

class SendcloudRequestException extends SendcloudClientException
{
    public function __construct(
        string $message = "",
        SendcloudExceptionCode $code = SendcloudExceptionCode::UNKNOWN,
        ?\Throwable $previous = null,
        protected ?int $sendcloudCode = null,
        protected ?string $sendcloudMessage = null,
        protected ?string $pointer = null,
    ) {
        $code = $code->value;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the code reported by Sendcloud when available. This usually equals the HTTP status code.
     */
    public function getSendcloudCode(): ?int
    {
        return $this->sendcloudCode;
    }

    /**
     * Returns the error message reported by Sendcloud when available.
     */
    public function getSendcloudMessage(): ?string
    {
        return $this->sendcloudMessage;
    }

    public function getPointer(): ?string
    {
        return $this->pointer;
    }

    public static function parseGuzzleException(
        TransferException $exception,
        string $defaultMessage = null,
    ): self {
        $message = $defaultMessage;
        $code = SendcloudExceptionCode::UNKNOWN;

        $responseCode = null;
        $responseMessage = null;
        $responsePointer = null;

        if ($exception instanceof RequestException && $exception->getMessage()) {
            $responseData = Json::decodeIfJson($exception->getResponse()->getBody(), true);
            $responseCode = (int)$responseData['errors'][0]['code'] ?? null;
            $responseMessage = $responseData['errors'][0]['detail'] ?? null;
            $responsePointer = $responseData['errors'][0]['source']['pointer'] ?? null;
        }

        if ($exception instanceof ConnectException) {
            $message = 'Could not contact Sendcloud API.';
            $code = SendcloudExceptionCode::CONNECTION_FAILED;
        }

        if ($exception->getCode() === 401) {
            $message = 'Invalid public/secret key combination.';
            $code = SendcloudExceptionCode::UNAUTHORIZED;
        } elseif ($exception->getCode() === 412) {
            $message = 'Sendcloud account is not fully configured yet.';

            if (stripos($responseMessage, 'no address data') !== false) {
                $code = SendcloudExceptionCode::NO_ADDRESS_DATA;
            } elseif (stripos($responseMessage, 'not allowed to announce') !== false) {
                $code = SendcloudExceptionCode::NOT_ALLOWED_TO_ANNOUNCE;
            }
        }

        return new self($message, $code, $exception, $responseCode, $responseMessage, $responsePointer);
    }
}
