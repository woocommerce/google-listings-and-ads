/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import AppButton from '.~/components/app-button';

const ConfirmButton = ( { onConfirm = noop } ) => {
	return (
		<AppButton isPrimary onClick={ onConfirm }>
			{ __( 'Confirm', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default ConfirmButton;
