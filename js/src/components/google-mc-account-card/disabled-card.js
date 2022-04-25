/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';

const DisabledCard = () => {
	return (
		<AccountCard
			disabled
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			indicator={
				<Button isSecondary disabled>
					{ __( 'Create Account', 'google-listings-and-ads' ) }
				</Button>
			}
		/>
	);
};

export default DisabledCard;
