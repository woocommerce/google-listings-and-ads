/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import './index.scss';

const AccountSelectControl = ( props ) => {
	const { accounts, ...rest } = props;

	if ( ! accounts ) {
		return <AppSpinner />;
	}

	const options = [
		{
			value: '',
			label: __( 'Select one', 'google-listings-and-ads' ),
		},
		...accounts.map( ( acc ) => ( {
			value: acc,
			label: __( 'Account ', 'google-listings-and-ads' ) + acc,
		} ) ),
	];

	return (
		<div className="gla-account-select-control">
			<SelectControl options={ options } { ...rest } />
		</div>
	);
};

export default AccountSelectControl;
