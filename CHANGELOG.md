# Release Notes for Craft Sendcloud Plugin

## 1.0.1 - 2021-07-12

### Added
- Customs invoice number and shipment type support.

### Changed
- Dependencies updated to improve Guzzle version compatibility.
- Plugin settings is now use order status handles instead of IDs to provide better inter-environment compatibility. **Please double-check your plugin settings after upgrading.**

### Fixed
- Race condition in the webhook handler.

## 1.0.0 - 2021-07-05

- Initial release.
