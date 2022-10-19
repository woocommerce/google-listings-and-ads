/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CreateAccountButton from './create-account-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';

const CreateAccountCard = ( props ) => {
	const { onCreateAccount } = props;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			indicator={
				<CreateAccountButton
					isSecondary
					onCreateAccount={ onCreateAccount }
				>
					{ __( 'Create account', 'google-listings-and-ads' ) }
				</CreateAccountButton>
			}
		/>
	);
};

export default CreateAccountCard;
