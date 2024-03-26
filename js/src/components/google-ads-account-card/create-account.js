/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import ClaimAccount from './claim-account';
import ClaimAccountModal from './claim-account-modal';
import ClaimTermsAndCreateAccountButton from './claim-terms-create-account-button';

const CreateAccount = ( props ) => {
	const { allowShowExisting, onShowExisting } = props;
	const [ showClaimModal, setShowClaimModal ] = useState( false );
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { hasAccess } = useGoogleAdsAccountStatus();

	const shouldClaimGoogleAdsAccount = Boolean(
		googleAdsAccount.id && hasAccess === false
	);

	const handleOnRequestClose = () => {
		setShowClaimModal( false );
	};

	const handleOnCreateAccount = () => {
		setShowClaimModal( true );
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			alignIcon="top"
			indicator={
				<ClaimTermsAndCreateAccountButton
					onCreateAccount={ handleOnCreateAccount }
				/>
			}
		>
			{ allowShowExisting && ! shouldClaimGoogleAdsAccount && (
				<Section.Card.Footer>
					<AppButton isLink onClick={ onShowExisting }>
						{ __(
							'Or, use your existing Google Ads account',
							'google-listings-and-ads'
						) }
					</AppButton>
				</Section.Card.Footer>
			) }

			{ shouldClaimGoogleAdsAccount && (
				<Fragment>
					{ showClaimModal && (
						<ClaimAccountModal
							onRequestClose={ handleOnRequestClose }
						/>
					) }

					<ClaimAccount />
				</Fragment>
			) }
		</AccountCard>
	);
};

export default CreateAccount;
