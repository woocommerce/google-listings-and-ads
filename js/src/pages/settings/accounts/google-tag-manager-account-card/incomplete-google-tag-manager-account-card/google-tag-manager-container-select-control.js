/**
 * Internal dependencies
 */
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import AppSelectControl from '~/components/app-select-control';

/**
 * Renders an `AppSelectControl` sourced from the candidate containers of the currently selected
 * Google Tag Manager account.
 *
 * @param {Object} props The component props, forwarded to `AppSelectControl`.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const GoogleTagManagerContainerSelectControl = ( props ) => {
	const { containers } = useGoogleTagManagerContainers();
	const options = containers?.map( ( container ) => ( {
		value: container.id,
		label: `${ container.name } (${ container.publicId })`,
	} ) );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default GoogleTagManagerContainerSelectControl;
