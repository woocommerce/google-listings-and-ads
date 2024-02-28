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
import useAutoCheckAdsAccountStatus from '.~/hooks/useAutoCheckAdsAccountStatus';
import AccountSwitch from '../account-switch';
import './index.scss';

const ClaimAccount = () => {
	const { inviteLink } = useGoogleAdsAccountStatus();
	useAutoCheckAdsAccountStatus();

	const handleClick = ( event ) => {
		const { defaultView } = event.target.ownerDocument;
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
			<Notice
				status="warning"
				isDismissible={ false }
				className="gla-ads-claim-account-notice"
			>
				{ createInterpolateElement(
					__(
						'Your new ads account has been created, but you do not have access to it yet. <link>Claim your new ads account</link> to automatically configure conversion tracking and configure onboarding',
						'google-listings-and-ads'
					),
					{ link: <ExternalLink href={ inviteLink } /> }
				) }
			</Notice>

			<Section.Card.Footer>
				<AccountSwitch />
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ClaimAccount;
