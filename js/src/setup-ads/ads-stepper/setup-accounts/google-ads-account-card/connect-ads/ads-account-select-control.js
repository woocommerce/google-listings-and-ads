/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSelectControl from '.~/components/app-select-control';
import toAccountText from '.~/utils/toAccountText';

const AdsAccountSelectControl = ( props ) => {
	const { accounts, ...rest } = props;

	const options = [
		{
			value: '',
			label: __( 'Select one', 'google-listings-and-ads' ),
		},
		...accounts.map( ( acc ) => ( {
			value: acc,
			label: toAccountText( acc ),
		} ) ),
	];

	return <AppSelectControl options={ options } { ...rest } />;
};

export default AdsAccountSelectControl;
