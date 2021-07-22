# HelpPopover

Renders a button with a `GridiconHelpOutline`. Upon clicking the button, it will display `children` in a `Popover`. It accepts an `id` prop to be used in track events.

## Usage

```jsx
<HelpPopover id="issues-to-resolve">Tooltip content.</HelpPopover>
```

## API

| Property | Description                    | Type      | Default |
| -------- | ------------------------------ | --------- | ------- |
| id       | Identifier used in track event | string    | -       |
| children | Tooltip content                | ReactNode | -       |
