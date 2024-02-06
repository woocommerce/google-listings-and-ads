/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';
import AppButton from '.~/components/app-button';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';

const EnhancedConversionFooter = () => {
	const { googleAdsAccount } = useGoogleAdsAccount();
	const {
		acceptedCustomerDataTerms: hasAcceptedTerms,
		hasFinishedResolution,
	} = useAcceptedCustomerDataTerms();

	const handleOnClick = useCallback( () => {
		if ( hasAcceptedTerms ) {
			console.log( 'Redirect the user to the TOS page.' );
		}
	}, [ hasAcceptedTerms ] );

	if (
		! googleAdsAccount ||
		googleAdsAccount?.status !== GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED
	) {
		return null;
	}

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
		</>
	);
};

export default EnhancedConversionFooter;
