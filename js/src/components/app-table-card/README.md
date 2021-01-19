# AppTableCard

Renders a TableCard component with additional styling, and record [track event](../../../../src/Tracking) when `trackEventReportId` is supplied via props.
 - `gla_table_header_toggle` upon toggling column visibility
 - `gla_table_sort` upon sorting table by column

## Usage

Same as [TableCard](https://woocommerce.github.io/woocommerce-admin/#/components/packages/table/README?id=tablecard).

## API

Same as [TableCard](https://woocommerce.github.io/woocommerce-admin/#/components/packages/table/README?id=tablecard) with the following additional props:

| Property | Description | Type | Default |
| --- | --- | --- | --- |
| trackEventReportId | Report ID to be used in track events. If this is not supplied, the track event will not be called. | string | - |
