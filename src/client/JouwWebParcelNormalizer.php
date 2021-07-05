<?php

namespace white\commerce\sendcloud\client;

use white\commerce\sendcloud\models\Address;
use white\commerce\sendcloud\models\Parcel;

final class JouwWebParcelNormalizer
{
    /**
     * @param \JouwWeb\SendCloud\Model\Parcel $data
     * @return Parcel
     */
    public function getParcel(\JouwWeb\SendCloud\Model\Parcel $data): Parcel
    {
        $parcel = new Parcel();
        $parcel->setOrderNumber($data->getOrderNumber());
        $parcel->setTrackingNumber($data->getTrackingNumber());
        $parcel->setTrackingUrl($data->getTrackingUrl());
        $parcel->setId($data->getId());
        $parcel->setStatusId($data->getStatusId());
        $parcel->setStatusMessage($data->getStatusMessage());
        $addressData = $data->getAddress();
        $parcel->setAddress(new Address(
            $addressData->getName(),
            $addressData->getCompanyName(),
            $addressData->getStreet(),
            $addressData->getHouseNumber(),
            $addressData->getCity(),
            $addressData->getPostalCode(),
            $addressData->getCountryCode(),
            $addressData->getEmailAddress(),
            $addressData->getPhoneNumber()
        ));
        $parcel->setCarrier($data->getCarrier());
        $parcel->setCreated($data->getCreated());
        $parcel->setServicePointId($data->getServicePointId());
        $parcel->setShippingMethodId($data->getShippingMethodId());
        $parcel->labelUrls[Parcel::LABEL_FORMAT_A6] = $data->getLabelUrl(Parcel::LABEL_FORMAT_A6);
        $parcel->labelUrls[Parcel::LABEL_FORMAT_A4_TOP_LEFT] = $data->getLabelUrl(Parcel::LABEL_FORMAT_A4_TOP_LEFT);
        $parcel->labelUrls[Parcel::LABEL_FORMAT_A4_TOP_RIGHT] = $data->getLabelUrl(Parcel::LABEL_FORMAT_A4_TOP_RIGHT);
        $parcel->labelUrls[Parcel::LABEL_FORMAT_A4_BOTTOM_LEFT] = $data->getLabelUrl(Parcel::LABEL_FORMAT_A4_BOTTOM_LEFT);
        $parcel->labelUrls[Parcel::LABEL_FORMAT_A4_BOTTOM_RIGHT] = $data->getLabelUrl(Parcel::LABEL_FORMAT_A4_BOTTOM_RIGHT);
        $parcel->setWeight($data->getWeight());

        return $parcel;
    }
}
