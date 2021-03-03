/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const AccountId = ( props ) => {
	const { id } = props;

	return createInterpolateElement(
		__( 'Account <accountid />', 'google-listings-and-ads' ),
		{
			accountid: <span>{ id }</span>,
		}
	);
};

export default AccountId;
