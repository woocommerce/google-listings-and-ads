/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { recordSetupAdsEvent } from '.~/utils/recordEvent';
import TopBar from '.~/components/stepper/top-bar';
import isFormDirty from '../is-form-dirty';

const SetupAdsTopBar = ( props ) => {
	const { formProps } = props;
	const shouldPreventClose = isFormDirty( formProps );

	const handleBackButtonClick = () => {
		if ( shouldPreventClose ) {
			// eslint-disable-next-line no-alert
			const result = window.confirm(
				__(
					'You have unsaved campaign data. Are you sure you want to leave?',
					'google-listings-and-ads'
				)
			);

			if ( ! result ) {
				return false;
			}
		}

		recordSetupAdsEvent( 'back' );
	};

	const handleHelpButtonClick = () => {
		recordSetupAdsEvent( 'help' );

		// TODO: navigate to where upon clicking help link?
	};

	return (
		<TopBar
			title={ __( 'Set up paid campaign', 'google-listings-and-ads' ) }
			backHref={ getNewPath( {}, '/google/dashboard' ) }
			onBackButtonClick={ handleBackButtonClick }
			onHelpButtonClick={ handleHelpButtonClick }
		/>
	);
};

export default SetupAdsTopBar;
