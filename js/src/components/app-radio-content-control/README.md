# AppRadioContentControl

Display additional content under the radio control. Optionally collapse the content of unchecked options, when the `collapsible` prop is set.

This uses `@wordpress/components` [RadioControl](https://github.com/WordPress/gutenberg/tree/master/packages/components/src/radio-control) under the hood.

## Usage

```jsx
<AppRadioContentControl
  { ...getInputProps( 'shippingRateOption' ) }
  label='Click me to see additional content'
  value="simple"
  collapsible
>
  My additional content here.
</AppRadioContentControl>
```

## API

Same as [InputControl](https://github.com/WordPress/gutenberg/tree/master/packages/components/src/input-control) API with the following noteworthy props.

| Property | Description | Type | Default |
| --- | --- | --- | --- |
| `label` | The option's label | Array | - |
| `value` | The option's value | String | - |
| `selected` | The selected `value` string | String | - |
| `collapsible` | Collapse the additional content when `selected !== value` | Boolean | - |
| `children` | The additional content to display | React.Node | - |
