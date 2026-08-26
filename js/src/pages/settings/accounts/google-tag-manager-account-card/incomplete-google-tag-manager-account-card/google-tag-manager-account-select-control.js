/**
 * Internal dependencies
 */
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import AppSelectControl from '~/components/app-select-control';

/**
 * Renders an `AppSelectControl` sourced from the candidate Google Tag Manager accounts.
 *
 * @param {Object} props The component props, forwarded to `AppSelectControl`.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const GoogleTagManagerAccountSelectControl = ( props ) => {
	const { account } = useGoogleTagManagerAccount();
	const options = account?.accounts?.map( ( acc ) => ( {
		value: acc.accountId,
		label: `${ acc.name } (${ acc.accountId })`,
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
