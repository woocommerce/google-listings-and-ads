/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import CreateAccountButton from './create-account-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';

const CreateAccountCard = ( props ) => {
	const {
		allowShowExisting,
		onShowExisting = () => {},
		onCreateAccount,
	} = props;

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
		>
			{ allowShowExisting && (
				<Section.Card.Footer>
					<AppButton isLink onClick={ onShowExisting }>
						{ __(
							'Or, use your existing Google Merchant Center account',
							'google-listings-and-ads'
						) }
					</AppButton>
				</Section.Card.Footer>
			) }
		</AccountCard>
	);
};

export default CreateAccountCard;
