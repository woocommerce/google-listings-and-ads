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
import { recordSetupMCEvent } from '../../utils/recordEvent';
import './index.scss';

const TopBar = () => {
	const handleBackLinkClick = () => {
		recordSetupMCEvent( 'back' );
	};

	const handleHelpButtonClick = () => {
		recordSetupMCEvent( 'help' );
	};

	return (
		<div className="gla-setup-mc-top-bar">
			<SetupBackLink
				type="wc-admin"
				href={ getNewPath( {}, '/google/start' ) }
				onClick={ handleBackLinkClick }
			/>
			<span className="title">
				{ __(
					'Get started with Google Listings & Ads',
					'google-listings-and-ads'
				) }
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
