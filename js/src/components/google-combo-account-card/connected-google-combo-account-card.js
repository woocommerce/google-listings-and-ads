
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import AppButton from '../app-button';
import ConnectedIconLabel from '../connected-icon-label';
import Section from '../../wcdl/section';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 *
 * @param {Object} props React props.
 * @param {{ email: string }} props.googleAccount A data payload object containing the user's Google account email.
 *
 * @fires gla_google_account_connect_different_account_button_click
 */
const ConnectedGoogleComboAccountCard = ( { googleAccount } ) => {
    const { data: existingMCAccounts, isResolving: MCAccountsResolving } = useExistingGoogleMCAccounts();
    const { existingAccounts: existingAdsAccount, isResolving: AdsAccountsResolving } = useExistingGoogleAdsAccounts();

    console.log('MCAccountsResolving', MCAccountsResolving);
    console.log('existingMCAccount', existingMCAccounts);
    //console.log('AdsAccountsResolving', AdsAccountsResolving);
    //console.log('existingAdsAccount', existingAdsAccount);

    return (
        <AccountCard
            appearance={ APPEARANCE.GOOGLE }
            description={ googleAccount.email }
            indicator={ <ConnectedIconLabel /> }
        >
            <Section.Card.Footer>
                <AppButton
                    isLink
                    disabled={ MCAccountsResolving || AdsAccountsResolving }
                    text={ __(
                        'Or, connect to a different Google account',
                        'google-listings-and-ads'
                    ) }
                    eventName="gla_google_account_connect_different_account_button_click"
                    onClick={ () => {} }
                />
            </Section.Card.Footer>
        </AccountCard>
    );
};

export default ConnectedGoogleComboAccountCard;
