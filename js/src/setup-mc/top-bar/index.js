/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TopBar from '.~/components/stepper/top-bar';
import { recordSetupMCEvent } from '.~/utils/recordEvent';

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
			onHelpButtonClick={ handleHelpButtonClick }
		/>
	);
};

export default SetupMCTopBar;
