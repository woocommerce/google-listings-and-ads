# AppIconButton

Make an Icon Button with icon on top and text at the bottom. This is essentially a wrapper around the `@wordpress/components` [Button](https://github.com/WordPress/gutenberg/tree/master/packages/components/src/button) component.

## Usage

```jsx
<AppIconButton
	icon={ <GridiconHelpOutline /> }
	text={ __( 'Help', 'google-listings-and-ads' ) }
/>
```

## API

Same as [Button](https://github.com/WordPress/gutenberg/tree/master/packages/components/src/button) API, with the following special props.

| Property | Description                    | Type   | Default |
| -------- | ------------------------------ | ------ | ------- |
| `icon`   | Icon component                 | Icon   | -       |
| `text`   | Text to display below the icon | String | -       |
