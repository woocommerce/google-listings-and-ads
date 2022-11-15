/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { ExternalLink } from 'extracted/@wordpress/components';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import toAccountText from '.~/utils/toAccountText';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';

/**
 * Renders a Google Ads account card UI with connected account information.
 *
 * @param {Object} props React props.
 * @param {{ id: number }} props.googleAdsAccount A data payload object containing the user's Google Ads account ID.
 * @param {boolean} [props.hideAccountSwitch=false] Indicate whether hide the account switch block at the card footer.
 * @param {JSX.Element} [props.children] Helper content below the Google account email.
 * @param {Object} props.restProps Props to be forwarded to AccountCard.
 */
export default function ConnectedGoogleAdsAccountCard( {
	googleAdsAccount,
	hideAccountSwitch = false,
	children,
	...restProps
} ) {
	const { disconnectGoogleAdsAccount } = useAppDispatch();
	const [ isDisconnecting, setDisconnecting ] = useState( false );

	const handleSwitch = () => {
		setDisconnecting( true );
		disconnectGoogleAdsAccount( true ).catch( () =>
			setDisconnecting( false )
		);
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			description={
				<ExternalLink href="https://ads.google.com/aw/overview">
					{ toAccountText( googleAdsAccount.id ) }
				</ExternalLink>
			}
			indicator={ <ConnectedIconLabel /> }
			{ ...restProps }
		>
			{ children }
			{ ! hideAccountSwitch && (
				<Section.Card.Footer>
					<AppButton
						isTertiary
						loading={ isDisconnecting }
						text={ __(
							'Or, connect to a different Google Ads account',
							'google-listings-and-ads'
						) }
						onClick={ handleSwitch }
					/>
				</Section.Card.Footer>
			) }
		</AccountCard>
	);
}
