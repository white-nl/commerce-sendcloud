# Release Notes for Craft Sendcloud Plugin

## Unreleased

### Added

 - Added Craft CMS 5 and Craft Commerce 5 compatibility.
 - Added multi-store support.
 - Added the "Manage sendcloud store settings" permission.
 - Added `white\commerce\sendcloud\services\ParcelItems::EVENT_CREATE_PARCEL_ITEM`.
 - Added the `applyShippingRules` setting to define if shipping rules in Sendcloud should be applied before creating the label.
 - Added the `labelFormat` setting to define the format of the labels.
 - Added `white\commerce\sendcloud\enums\LabelFormat`.
 - Added `white\commerce\sendcloud\enums\ParcelStatus`.
 - Added `white\commerce\sendcloud\enums\SendcloudExceptionCode`.
 - Added `white\commerce\sendcloud\enums\ShipmentType`.
 - Added an option to update the Sendcloud integration.
 - Added `white\commerce\sendcloud\cp\StoreSettingsController`.
 - Added `white\commerce\sendcloud\events\ParcelEvent`.
 - Added `white\commerce\sendcloud\events\ParcelItemEvent`.
 - Added `white\commerce\sendcloud\exception\SendcloudClientException`.
 - Added `white\commerce\sendcloud\exception\SendcloudRequestException`.
 - Added `white\commerce\sendcloud\exception\SendcloudStateException`.
 - Added `white\commerce\sendcloud\models\Parcel::$contract`.
 - Added `white\commerce\sendcloud\models\Parcel::$requestLabel`.
 - Added `white\commerce\sendcloud\models\Parcel::$email`.
 - Added `white\commerce\sendcloud\models\Parcel::$shippingMethod`.
 - Added `white\commerce\sendcloud\models\Parcel::$insuredValue`.
 - Added `white\commerce\sendcloud\models\Parcel::$totalOrderValueCurrency`.
 - Added `white\commerce\sendcloud\models\Parcel::$totalOrderValue`.
 - Added `white\commerce\sendcloud\models\Parcel::$quantity`.
 - Added `white\commerce\sendcloud\models\Parcel::$shippingMethodCheckoutName`.
 - Added `white\commerce\sendcloud\models\Parcel::$toPostNumber`.
 - Added `white\commerce\sendcloud\models\Parcel::$senderAddress`.
 - Added `white\commerce\sendcloud\models\Parcel::$customsInvoiceNr`.
 - Added `white\commerce\sendcloud\models\Parcel::$customsShipmentType`.
 - Added `white\commerce\sendcloud\models\Parcel::$reference`.
 - Added `white\commerce\sendcloud\models\Parcel::$externalReference`.
 - Added `white\commerce\sendcloud\models\Parcel::$toServicePointId`.
 - Added `white\commerce\sendcloud\models\Parcel::$totalInsuredValue`.
 - Added `white\commerce\sendcloud\models\Parcel::$shipmentUuid`.
 - Added `white\commerce\sendcloud\models\Parcel::$parcelItems`.
 - Added `white\commerce\sendcloud\models\Parcel::$isReturn`.
 - Added `white\commerce\sendcloud\models\Parcel::$length`.
 - Added `white\commerce\sendcloud\models\Parcel::$width`.
 - Added `white\commerce\sendcloud\models\Parcel::$height`.
 - Added `white\commerce\sendcloud\models\Parcel::$requestLabelAsync`.
 - Added `white\commerce\sendcloud\models\Parcel::$applyShippingRules`.
 - Added `white\commerce\sendcloud\models\Parcel::$returnSenderAddress`.
 - Added `white\commerce\sendcloud\models\Parcel::$parcelStatus`.
 - Added `white\commerce\sendcloud\models\ParcelItem::$productId`.
 - Added `white\commerce\sendcloud\models\ParcelItem::$properties`.
 - Added `white\commerce\sendcloud\models\ParcelItem::$itemId`.
 - Added `white\commerce\sendcloud\models\ParcelItem::$returnReason`.
 - Added `white\commerce\sendcloud\models\ParcelItem::$returnMessage`.
 - Added `white\commerce\sendcloud\models\ParcelItem::$midCode`.
 - Added `white\commerce\sendcloud\models\ParcelItem::$materialContent`.
 - Added `white\commerce\sendcloud\models\ParcelItem::$intendedUse`.
 - Added `white\commerce\sendcloud\models\ShippingMethod`.
 - Added `white\commerce\sendcloud\models\StatusMapping`.
 - Added `white\commerce\sendcloud\services\ParcelItems`.
 - Added `white\commerce\sendcloud\services\StatusMapping`.

### Removed

 - Removed the `jouwweb/sendcloud` dependency
 - Removed `white\commerce\sendcloud\client\JouwWebSendcloudAdapter::EVENT_AFTER_CREATE_ADDRESS`. `white\commerce\sendcloud\client\SendcloudClient::EVENT_AFTER_CREATE_ADDRESS` should be used instead.
 - Removed `white\commerce\sendcloud\client\JouwWebSendcloudAdapter::EVENT_BEFORE_SET_PARCEL_WEIGHT_EVENT`. `white\commerce\sendcloud\client\SendcloudClient::EVENT_BEFORE_PUSH_PARCEL` should be used instead.
 - Removed the `pluginNameOverride` setting.
 - Removed `white\commerce\sendcloud\client\JouwWebParcelNormalizer`.
 - Removed `white\commerce\sendcloud\client\JouwWebSendcloudAdapter`.
 - Removed `white\commerce\sendcloud\client\SendcloudInterface`.
 - Removed `white\commerce\sendcloud\client\WebhookParcelNormalizer`.
 - Removed `white\commerce\sendcloud\events\ParcelWeightEvent`.
 - Removed `white\commerce\sendcloud\models\Parcel::$servicePointId`. `white\commerce\sendcloud\models\Parcel::$toServicePointId` should be used instead.
 - Added `white\commerce\sendcloud\models\Parcel::$shippingMethodId`.
 - Added `white\commerce\sendcloud\models\Parcel::$statusMessage`.
 - Added `white\commerce\sendcloud\models\Parcel::$statusId`.

## 3.1.0 - 2024-04-24

### Added

 - Added an `EVENT_BEFORE_SET_PARCEL_WEIGHT` event to manipulate the parcel weight

## 3.0.2 - 2024-03-11

### Fixed
- Fixed pushing a order to sendcloud when the address has an administrativeArea

### Added
- Added new Sendcloud statuses

## 3.0.1 - 2023-12-11

### Fixed
- Fixed pushing a order to Sendcloud when it doesn't have a sendcloud shipping method

## 3.0.0 - 2023-10-16

### Added
- Added the EVENT_AFTER_CREATE_ADDRESS

### Changed
- Updated `jouwweb/sendcloud` to 5.0.1
- The country is now getting send as city if the Craft address doesn't have a locality ([#15](https://github.com/white-nl/commerce-sendcloud/issues/15))
- AddressLine2 is no longer used for houseNubmer. Use the new event to manipulate the data if needed ([#16](https://github.com/white-nl/commerce-sendcloud/issues/16))

### Fixed
- The postalCode is now set as empty string when not available ([#17](https://github.com/white-nl/commerce-sendcloud/issues/17))
- Updated the example-templates to work again with the commerce example-templates

## 2.2.1 - 2023-06-22

### Fixed
- Fixed a typo on the settings page

## 2.2.0 - 2023-06-22

### Added
- Added an option to configure the Sendcloud order number ([#1](https://github.com/white-nl/commerce-sendcloud/issues/1))
- Added a setting to set the queue jobs priority

## 2.1.3 - 2023-04-04

### Changed
- Updated `jouwweb/sendcloud` to 3.10.2

### Fixed 
- Fixed missing countryStateCode in shipping address ([#12](https://github.com/white-nl/commerce-sendcloud/issues/12)) 

## 2.1.2 - 2022-07-13

### Fixed
- Fixed an error with setting up the integration if the CP runs on a separate domain

## 2.1.1 - 2022-06-15

### Fixed
- Fixed an error that could occure when installing the plugin.

## 2.1.0 - 2022-06-08

### Changed
- Moved the creation of Sendcloud parcels to a queue job

## 2.0.0 - 2022-05-31

### Added
- Added Craft CMS 4 and Craft Commerce 4 compatibility
- Added caching for retrieving the shipping methods from Sendcloud

## 1.0.5 - 2022-02-23

### Changed
- Total order value added to the parcel data.
- Address line 3 now gets appended to the house number.

## 1.0.4 - 2021-11-09

### Fixed
- Total order weight calculation fixed.

## 1.0.3 - 2021-09-13

### Fixed
- Remove service point information if the shipping method doesn't match any Sendcloud method

## 1.0.2 - 2021-08-13

### Fixed
- Proper weight unit conversion depending on your Craft settings.

## 1.0.1 - 2021-07-12

### Added
- Customs invoice number and shipment type support.

### Changed
- Dependencies updated to improve Guzzle version compatibility.
- Plugin settings is now using order status handles instead of IDs to provide better inter-environment compatibility. **Please double-check your plugin settings after upgrading.**

### Fixed
- Race condition in the webhook handler.

## 1.0.0 - 2021-07-05

- Initial release.
