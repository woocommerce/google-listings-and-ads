/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppButton from '.~/components/app-button';

const Footer = ( { handleGuideFinish } ) => {
	if ( glaData.adsConnected ) {
		return null;
	}

	return (
		<AppButton
			isPrimary
			data-action="view-product-feed"
			onClick={ handleGuideFinish }
		>
			{ __( 'View product feed', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default Footer;
