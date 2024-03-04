/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import noop from 'lodash/noop';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppButton from '.~/components/app-button';

const Footer = ( { onClose = noop } ) => {
	if ( glaData.adsConnected ) {
		return null;
	}

	return (
		<AppButton
			isPrimary
			data-action="view-product-feed"
			onClick={ onClose }
		>
			{ __( 'View product feed', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default Footer;
