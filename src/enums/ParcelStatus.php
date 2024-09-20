<?php

namespace white\commerce\sendcloud\enums;

use Craft;

enum ParcelStatus: int
{
    case ANNOUNCED = 1;
    case EN_ROUTE_TO_SORTING_CENTER = 3;
    case DELIVERY_DELAYED = 4;
    case SORTED = 5;
    case NOT_SORTED = 6;
    case BEING_SORTED = 7;
    case DELIVERY_ATTEMPT_FAILED = 8;
    case DELIVERED = 11;
    case AWAITING_CUSTOMER_PICKUP = 12;
    case ANNOUNCED_NOT_COLLECTED = 13;
    case ERROR_COLLECTING = 15;
    case SHIPMENT_PICKED_UP_BY_DRIVER = 22;
    case UNABLE_TO_DELIVER = 80;
    case PARCEL_EN_ROUTE = 91;
    case DRIVER_EN_ROUTE = 92;
    case SHIPMENT_COLLECTED_BY_CUSTOMER = 93;
    case PARCEL_CANCELLATION_FAILED = 94;
    case NO_LABEL = 999;
    case READY_TO_SEND = 1000;
    case BEING_ANNOUNCED = 1001;
    case ANNOUNCEMENT_FAILED = 1002;
    case UNKNOWN_STATUS_CHECK_CARRIER_TRACK_TRACE_PAGE_FOR_MORE_INSIGHTS = 1337;
    case CANCELLED_UPSTREAM = 1998;
    case CANCELLATION_REQUESTED = 1999;
    case CANCELLED = 2000;
    case SUBMITTING_CANCELLATION_REQUEST = 2001;
    case AT_CUSTOMS = 62989;
    case AT_SORTING_CENTRE = 62990;
    case REFUSED_BY_RECIPIENT = 62991;
    case RETURNED_TO_SENDER = 62992;
    case DELIVERY_METHOD_CHANGED = 62993;
    case DELIVERY_DATE_CHANGED = 62994;
    case DELIVERY_ADDRESS_CHANGED = 62995;
    case EXCEPTION = 62996;
    case ADDRESS_INVALID = 62997;


    public function getMessage(): string
    {
        return match ($this) {
            self::ANNOUNCED => Craft::t('commerce-sendcloud', 'Announcement'),
            self::EN_ROUTE_TO_SORTING_CENTER => Craft::t('commerce-sendcloud', 'En route to sorting center'),
            self::DELIVERY_DELAYED => Craft::t('commerce-sendcloud', 'Delivery delayed'),
            self::SORTED => Craft::t('commerce-sendcloud', 'Sorted'),
            self::NOT_SORTED => Craft::t('commerce-sendcloud', 'Not sorted'),
            self::BEING_SORTED => Craft::t('commerce-sendcloud', 'Being sorted'),
            self::DELIVERY_ATTEMPT_FAILED => Craft::t('commerce-sendcloud', 'Delivery attempt failed'),
            self::DELIVERED => Craft::t('commerce-sendcloud', 'Delivered'),
            self::AWAITING_CUSTOMER_PICKUP => Craft::t('commerce-sendcloud', 'Awaiting customer pickup'),
            self::ANNOUNCED_NOT_COLLECTED => Craft::t('commerce-sendcloud', 'Announced: not collected'),
            self::ERROR_COLLECTING => Craft::t('commerce-sendcloud', 'Error collecting'),
            self::SHIPMENT_PICKED_UP_BY_DRIVER => Craft::t('commerce-sendcloud', 'Shipment picked up by driver'),
            self::UNABLE_TO_DELIVER => Craft::t('commerce-sendcloud', 'Unable to deliver'),
            self::PARCEL_EN_ROUTE => Craft::t('commerce-sendcloud', 'Parcel en route'),
            self::DRIVER_EN_ROUTE => Craft::t('commerce-sendcloud', 'Driver en route'),
            self::SHIPMENT_COLLECTED_BY_CUSTOMER => Craft::t('commerce-sendcloud', 'Shipment collected by customer'),
            self::PARCEL_CANCELLATION_FAILED => Craft::t('commerce-sendcloud', 'Parcel cancellation failed'),
            self::NO_LABEL => Craft::t('commerce-sendcloud', 'No label'),
            self::READY_TO_SEND => Craft::t('commerce-sendcloud', 'Ready to send'),
            self::BEING_ANNOUNCED => Craft::t('commerce-sendcloud', 'Being announced'),
            self::ANNOUNCEMENT_FAILED => Craft::t('commerce-sendcloud', 'Announcement failed'),
            self::UNKNOWN_STATUS_CHECK_CARRIER_TRACK_TRACE_PAGE_FOR_MORE_INSIGHTS => Craft::t('commerce-sendcloud', 'Unknown status - check carrier track & trace page for more insights'),
            self::CANCELLED_UPSTREAM => Craft::t('commerce-sendcloud', 'Cancelled upstream'),
            self::CANCELLATION_REQUESTED => Craft::t('commerce-sendcloud', 'Cancellation requested'),
            self::CANCELLED => Craft::t('commerce-sendcloud', 'Cancelled'),
            self::SUBMITTING_CANCELLATION_REQUEST => Craft::t('commerce-sendcloud', 'Submitting cancellation request'),
            self::AT_CUSTOMS => Craft::t('commerce-sendcloud', 'At Customs'),
            self::AT_SORTING_CENTRE => Craft::t('commerce-sendcloud', 'At sorting centre'),
            self::REFUSED_BY_RECIPIENT => Craft::t('commerce-sendcloud', 'Refused by recipient'),
            self::RETURNED_TO_SENDER => Craft::t('commerce-sendcloud', 'Returned to sender'),
            self::DELIVERY_METHOD_CHANGED => Craft::t('commerce-sendcloud', 'Delivery method changed'),
            self::DELIVERY_DATE_CHANGED => Craft::t('commerce-sendcloud', 'Delivery date changed'),
            self::DELIVERY_ADDRESS_CHANGED => Craft::t('commerce-sendcloud', 'Delivery address changed'),
            self::EXCEPTION => Craft::t('commerce-sendcloud', 'Exception'),
            self::ADDRESS_INVALID => Craft::t('commerce-sendcloud', 'Address invalid'),
        };
    }

    public function getLabel(): string {
        return sprintf('%d: %s', $this->value, $this->getMessage());
    }
}