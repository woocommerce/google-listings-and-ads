/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import AppIconButton from '../../components/app-icon-button';
import SetupBackLink from '../../components/setup-back-link';
import { recordSetupAdsEvent } from '../../utils/recordEvent';
import './index.scss';

const TopBar = () => {
	const handleBackLinkClick = () => {
		recordSetupAdsEvent( 'back' );
	};

	const handleHelpButtonClick = () => {
		recordSetupAdsEvent( 'help' );
	};

	return (
		<div className="gla-setup-ads-top-bar">
			<SetupBackLink
				type="wc-admin"
				href={ getNewPath( {}, '/google/dashboard' ) }
				onClick={ handleBackLinkClick }
			/>
			<span className="title">
				{ __( 'Set up paid campaign', 'google-listings-and-ads' ) }
			</span>
			<div className="actions">
				{ /* TODO: click and navigate to where? */ }
				<AppIconButton
					icon={ <GridiconHelpOutline /> }
					text={ __( 'Help', 'google-listings-and-ads' ) }
					onClick={ handleHelpButtonClick }
				/>
			</div>
		</div>
	);
};

export default TopBar;
