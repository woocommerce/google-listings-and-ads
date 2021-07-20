# AppModal

AppModal renders a Modal component with custom footer styling for placing buttons across different screen sizes. It accepts a `buttons` array prop. The last button in the `buttons` array should be the primary button.

On small screen, the buttons will render with full width in a stack, with the last button (primary button) on top.

On mobile and bigger screens, the buttons will render as normal buttons and are aligned to the right side of the modal.

## Usage

```jsx
<AppModal
	title="Modal title here"
	buttons={ [
		<Button key="secondary" isSecondary onClick={ handleCancelClick }>
			Cancel
		</Button>,
		<Button key="primary" isPrimary onClick={ handleOkClick }>
			OK
		</Button>,
	] }
	onRequestClose={ onRequestClose }
>
	<p>Modal content here.</p>
</AppModal>
```

## API

Same as Modal component, with the following additional props.

| Property  | Description                                                                                       | Type            | Default |
| --------- | ------------------------------------------------------------------------------------------------- | --------------- | ------- |
| `buttons` | Buttons to be rendered in the footer. The primary button should be the last element in the array. | Array<`Button`> | []      |
