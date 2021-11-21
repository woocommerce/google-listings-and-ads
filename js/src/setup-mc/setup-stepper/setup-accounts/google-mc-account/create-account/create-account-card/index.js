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
			description={ __(
				'Create your Google Merchant Center account',
				'google-listings-and-ads'
			) }
			indicator={
				<CreateAccountButton onCreateAccount={ onCreateAccount } />
			}
		/>
	);
};

export default CreateAccountCard;
