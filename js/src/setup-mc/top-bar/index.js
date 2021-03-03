/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import AppIconButton from '.~/components/app-icon-button';
import TopBar from '.~/components/edit-program/top-bar';
import { recordSetupMCEvent } from '.~/utils/recordEvent';
import './index.scss';

const SetupMCTopBar = () => {
	const handleBackButtonClick = () => {
		recordSetupMCEvent( 'back' );
	};

	const handleHelpButtonClick = () => {
		recordSetupMCEvent( 'help' );
	};

	return (
		<TopBar
			backHref={ getNewPath( {}, '/google/start' ) }
			onBackButtonClick={ handleBackButtonClick }
		>
			<div className="actions">
				{ /* TODO: click and navigate to where? */ }
				<AppIconButton
					icon={ <GridiconHelpOutline /> }
					text={ __( 'Help', 'google-listings-and-ads' ) }
					onClick={ handleHelpButtonClick }
				/>
			</div>
		</TopBar>
	);
};

export default SetupMCTopBar;
