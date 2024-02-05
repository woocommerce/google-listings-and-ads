/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import useGoogleAccountCheck from '.~/hooks/useGoogleAccountCheck';

const DisabledCard = () => {
	const { google } = useGoogleAccountCheck();

	return (
		<AccountCard
			disabled
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			indicator={
				google &&
				google.active === 'yes' && (
					<AppButton isSecondary disabled>
						{ __( 'Create account', 'google-listings-and-ads' ) }
					</AppButton>
				)
			}
		/>
	);
};

export default DisabledCard;
