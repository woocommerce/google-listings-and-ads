/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import AppButton from '.~/components/app-button';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';

const EnhancedConversionFooter = ( { handleGuideFinish } ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const {
		acceptedCustomerDataTerms: hasAcceptedTerms,
		hasFinishedResolution,
	} = useAcceptedCustomerDataTerms();

	// @todo: Review URL
	const TOS_URL = 'https://ads.google.com/aw/conversions/customersettings';

	const handleOnClick = useCallback( () => {
		if ( hasAcceptedTerms ) {
			updateEnhancedAdsConversionStatus(
				ENHANCED_ADS_CONVERSION_STATUS.ENABLED
			);

			createNotice(
				'info',
				__( 'Status succesfully set', 'google-listings-and-ads' )
			);

			handleGuideFinish?.();
			return;
		}

		window.open( TOS_URL, '_blank' );
		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.PENDING
		);
	}, [
		hasAcceptedTerms,
		updateEnhancedAdsConversionStatus,
		createNotice,
		handleGuideFinish,
	] );

	return (
		<>
			<div className="gla-submission-success-guide__space_holder" />

			{ ! hasFinishedResolution && <AppButton isPrimary loading /> }

			{ hasFinishedResolution && (
				<AppButton onClick={ handleOnClick } isPrimary>
					{ ! hasAcceptedTerms &&
						__(
							'Sign terms of service on Google Ads',
							'google-listings-and-ads'
						) }

					{ hasAcceptedTerms &&
						__( 'Confirm', 'google-listings-and-ads' ) }
				</AppButton>
			) }

			<AppButton
				isSecondary
				// @todo: review data-action label
				data-action="view-product-feed"
				onClick={ handleGuideFinish }
			>
				{ __( 'Close', 'google-listings-and-ads' ) }
			</AppButton>
		</>
	);
};

export default EnhancedConversionFooter;
