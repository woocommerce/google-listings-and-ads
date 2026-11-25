/**
 * Internal dependencies
 */
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import AppSelectControl from '~/components/app-select-control';

/**
 * Renders an `AppSelectControl` sourced from the candidate Google Tag Manager accounts.
 *
 * @param {Object} props The component props, forwarded to `AppSelectControl`.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const GoogleTagManagerAccountSelectControl = ( props ) => {
	const { existingAccounts } = useExistingGoogleTagManagerAccounts();
	const options = existingAccounts?.map( ( acc ) => ( {
		value: acc.id,
		label: `${ acc.name } (${ acc.id })`,
	} ) );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default GoogleTagManagerAccountSelectControl;
