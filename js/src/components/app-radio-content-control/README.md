# AppRadioContentControl

Display additional content when the radio button is checked.

This uses `@wordpress/components` [RadioControl](https://github.com/WordPress/gutenberg/tree/master/packages/components/src/radio-control) under the hood.

## Usage

```jsx
<AppRadioContentControl
  { ...getInputProps( 'shippingRateOption' ) }
  label='Click me to see additional content'
  value="simple"
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
| `children` | The additional content to display when `selected === value` | React.Node | - |
