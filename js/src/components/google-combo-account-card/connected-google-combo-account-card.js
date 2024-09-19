/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import CreateMCAdsAccounts from './create-mc-ads-accounts';

/**
 * Clicking on the "connect to a different Google account" button.
 *
 * @event gla_google_account_connect_different_account_button_click
 */

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 *
 * @param {Object} props React props.
 * @param {{ MCAccount: string }} props.MCAccount The Google Merchant Center account.
 * @param {{ AdsAccounts: string }} props.AdsAccounts The Google Ads accounts.
 *
 * @fires gla_google_account_connect_different_account_button_click
 */
const ConnectedGoogleComboAccountCard = ( { MCAccounts, AdsAccounts } ) => {
    return (
        <AccountCard
            appearance={ APPEARANCE.GOOGLE }
            description={ __(
                'You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you.',
                'google-listings-and-ads'
            ) }
            helper={ createInterpolateElement(
                __(
                    '<p>Merchant Center is required to sync products so they show on Google. Google Ads is required to set up conversion measurement for your store.</p>',
                    'google-listings-and-ads'
                ),
                {
                    p: <p></p>,
                }
            ) }
            indicator={ __( 'Creating…', 'google-listings-and-ads' ) }
        >
            { MCAccounts.length === 0 && AdsAccounts.length === 0 && <CreateMCAdsAccounts /> }
        </AccountCard>
    );
};

export default ConnectedGoogleComboAccountCard;
