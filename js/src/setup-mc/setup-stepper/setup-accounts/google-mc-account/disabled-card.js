/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import DisabledDiv from '.~/components/disabled-div';

const DisabledCard = () => {
	return (
		<DisabledDiv>
			<AccountCard
				appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
				description={ __(
					'Required to sync products and list on Google',
					'google-listings-and-ads'
				) }
				indicator={
					<Button isSecondary disabled>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</Button>
				}
			/>
		</DisabledDiv>
	);
};

export default DisabledCard;
