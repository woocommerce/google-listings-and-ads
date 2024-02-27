/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice, ExternalLink } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import getWindowFeatures from '.~/utils/getWindowFeatures';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import AccountSwitch from './account-switch';
import ClaimPending from './claim-pending';

const ClaimAccount = ( { claimModalOpen = false } ) => {
	const { inviteLink } = useGoogleAdsAccountStatus();

	const handleClick = ( e ) => {
		const { defaultView } = e.target.ownerDocument;
		const features = getWindowFeatures( defaultView, 600, 800 );

		defaultView.open( inviteLink, '_blank', features );
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			alignIcon="top"
			indicator={
				<AppButton isSecondary onClick={ handleClick }>
					{ __( 'Claim Account', 'google-listings-and-ads' ) }
				</AppButton>
			}
		>
			<Section.Card.Body>
				<Notice status="warning" isDismissible={ false }>
					{ createInterpolateElement(
						__(
							'Your new ads account has been created, but you do not have access to it yet. <link>Claim your new ads account</link> to automatically configure conversion tracking and configure onboarding',
							'google-listings-and-ads'
						),
						{ link: <ExternalLink href={ inviteLink } /> }
					) }
				</Notice>
			</Section.Card.Body>

			<Section.Card.Footer>
				<AccountSwitch />
			</Section.Card.Footer>

			{ /* The ClaimPending component is also present in he ClaimAccountModal. Loading only one of it to prevent duplicate requests to the API */ }
			{ ! claimModalOpen && <ClaimPending /> }
		</AccountCard>
	);
};

export default ClaimAccount;
