# AppCountryMultiSelect

Renders a `@woocommerce/components` [SelectControl](https://woocommerce.github.io/woocommerce-admin/#/components/packages/select-control/README) that displays countries in a dropdown. Multiple countries can be selected.

## Usage

```jsx
<AppCountryMultiSelect
  value={ selected }
  onChange={ handleChange }
/>
```

## API

Same as [SelectControl](https://woocommerce.github.io/woocommerce-admin/#/components/packages/select-control/README) API, with the following noteworthy props.

| Property | Description | Type | Default |
| --- | --- | --- | --- |
| `value` | Selected country values. | Array | [] |
| `onChange` | Text to display below the icon | Function | - |
