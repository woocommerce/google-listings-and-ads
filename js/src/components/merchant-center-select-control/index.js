/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import AppSelectControl from '.~/components/app-select-control';

/**
 * @param {Object} props The component props
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const MerchantCenterSelectControl = ( props ) => {
	const { data: existingAccounts = [] } = useExistingGoogleMCAccounts();
	const options = existingAccounts?.map( ( acc ) => {
		return {
			value: acc.id,
			label: sprintf(
				// translators: 1: account name, 2: account domain, 3: account ID.
				__( '%1$s ・ %2$s (%3$s)', 'google-listings-and-ads' ),
				acc.name,
				acc.domain,
				acc.id
			),
		};
	} );
	options.sort( ( a, b ) => {
		return a.label.localeCompare( b.label );
	} );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default MerchantCenterSelectControl;
