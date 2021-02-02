/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SetupHelpButton from '../../components/setup-help-button';
import SetupBackLink from '../../components/setup-back-link';
import SetupTopBar from '../../components/setup-top-bar';
import { recordSetupMCEvent } from '../../utils/recordEvent';

const TopBar = () => {
	const handleBackLinkClick = () => {
		recordSetupMCEvent( 'back' );
	};

	const handleHelpButtonClick = () => {
		recordSetupMCEvent( 'help' );
	};

	return (
		<SetupTopBar
			backLink={
				<SetupBackLink
					type="wc-admin"
					href={ getNewPath( {}, '/google/start' ) }
					onClick={ handleBackLinkClick }
				/>
			}
			title={ __(
				'Get started with Google Listings & Ads',
				'google-listings-and-ads'
			) }
			helpButton={ <SetupHelpButton onClick={ handleHelpButtonClick } /> }
		/>
	);
};

export default TopBar;
