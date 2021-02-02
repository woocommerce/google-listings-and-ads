/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SetupBackLink from '../components/setup-back-link';
import SetupHelpButton from '../components/setup-help-button';
import SetupTopBar from '../components/setup-top-bar';
import { recordSetupAdsEvent } from '../utils/recordEvent';

const TopBar = () => {
	const handleBackLinkClick = () => {
		recordSetupAdsEvent( 'back' );
	};

	const handleHelpButtonClick = () => {
		recordSetupAdsEvent( 'help' );
	};

	return (
		<SetupTopBar
			backLink={
				<SetupBackLink
					type="wc-admin"
					href={ getNewPath( {}, '/google/dashboard' ) }
					onClick={ handleBackLinkClick }
				/>
			}
			title={ __( 'Set up paid campaign', 'google-listings-and-ads' ) }
			helpButton={ <SetupHelpButton onClick={ handleHelpButtonClick } /> }
		/>
	);
};

export default TopBar;
