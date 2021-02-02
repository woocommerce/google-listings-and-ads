/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import GridiconChevronLeft from 'gridicons/dist/chevron-left';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import AppIconButton from '../../components/app-icon-button';
import { recordSetupAdsEvent } from '../../utils/recordEvent';
import './index.scss';

const TopBar = () => {
	const handleBackButtonClick = () => {
		recordSetupAdsEvent( 'back' );
	};

	const handleHelpButtonClick = () => {
		recordSetupAdsEvent( 'help' );
	};

	return (
		<div className="gla-setup-ads-top-bar">
			<Link
				className="back-button"
				href={ getNewPath( {}, '/google/start' ) }
				type="wc-admin"
				onClick={ handleBackButtonClick }
			>
				<GridiconChevronLeft />
			</Link>
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
