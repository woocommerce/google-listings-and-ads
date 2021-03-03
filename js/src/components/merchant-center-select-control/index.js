/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import AppSpinner from '.~/components/app-spinner';
import './index.scss';

const MerchantCenterSelectControl = ( props ) => {
	const { value, onChange } = props;
	const { existingAccounts } = useExistingGoogleMCAccounts();

	if ( ! existingAccounts ) {
		return <AppSpinner />;
	}

	const options = [
		{
			value: '',
			label: __( 'Select one', 'google-listings-and-ads' ),
		},
		...existingAccounts.map( ( acc ) => ( {
			value: acc.id,
			label: acc.id,
		} ) ),
	];

	return (
		<div className="gla-merchant-center-select-control">
			<SelectControl
				options={ options }
				value={ value }
				onChange={ onChange }
			/>
		</div>
	);
};

export default MerchantCenterSelectControl;
