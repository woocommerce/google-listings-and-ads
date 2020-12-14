# AppTableCard

Renders a TableCard component with additional styling, and record `gla_table_header_toggle` [track event](../../../../src/Tracking) upon toggling column visibility when `trackEventReportId` is supplied via props.

## Usage

Same as [TableCard](https://woocommerce.github.io/woocommerce-admin/#/components/packages/table/README?id=tablecard).

## API

Same as [TableCard](https://woocommerce.github.io/woocommerce-admin/#/components/packages/table/README?id=tablecard) with the following additional props:

| Property | Description | Type | Default |
| --- | --- | --- | --- |
| trackEventReportId | Report ID to be used in `gla_table_header_toggle` track event. If this is not supplied, the track event will not be called. | string | - |
