/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSelectControl from '.~/components/app-select-control';

const AdsAccountSelectControl = ( props ) => {
	const { accounts, ...rest } = props;

	const options = [
		{
			value: '',
			label: __( 'Select one', 'google-listings-and-ads' ),
		},
		...accounts.map( ( acc ) => ( {
			value: acc.id,
			label: sprintf(
				// translators: 1: account name, 2: account ID.
				__( '%1$s (%2$s)', 'google-listings-and-ads' ),
				acc.name,
				acc.id
			),
		} ) ),
	];

	return <AppSelectControl options={ options } { ...rest } />;
};

export default AdsAccountSelectControl;
