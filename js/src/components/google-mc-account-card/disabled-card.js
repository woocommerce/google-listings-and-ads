/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';

const DisabledCard = () => {
	return (
		<AccountCard
			disabled
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			indicator={
				<AppButton isSecondary disabled>
					{ __( 'Create account', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default DisabledCard;
