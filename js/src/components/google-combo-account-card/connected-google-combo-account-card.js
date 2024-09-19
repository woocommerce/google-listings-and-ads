/**
 * External dependencies
 */
import {
	createInterpolateElement,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import CreateAccounts from './create-accounts';

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
 * @param {{ googleAccount: object }} props.googleAccount The Google account.
 * @param {{ MCAccounts: string }} props.MCAccounts The Google Merchant Center account.
 * @param {{ AdsAccounts: string }} props.AdsAccounts The Google Ads accounts.
 *
 * @fires gla_google_account_connect_different_account_button_click
 */
const ConnectedGoogleComboAccountCard = ( {
	googleAccount,
	MCAccounts,
	AdsAccounts,
} ) => {
	const [ accounts, setAccounts ] = useState( {
		MCAccounts,
		AdsAccounts,
	} );

	useEffect( () => {
		if ( MCAccounts.length ) {
			setAccounts( {
				...accounts,
				MCAccounts,
			} );
		}

		if ( AdsAccounts.length ) {
			setAccounts( {
				...accounts,
				AdsAccounts,
			} );
		}
	}, [ MCAccounts, AdsAccounts, accounts ] );

	const existingAccounts = Object.keys( accounts ).some(
		( account ) => accounts[ account ].length > 0
	);

	const description = ! existingAccounts
		? createInterpolateElement(
				__(
					'<p>You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you.</p>',
					'google-listings-and-ads'
				),
				{
					p: <p></p>,
				}
		  )
		: '';

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			description={ description }
			helper={ createInterpolateElement(
				__(
					'<p>Merchant Center is required to sync products so they show on Google. Google Ads is required to set up conversion measurement for your store.</p>',
					'google-listings-and-ads'
				),
				{
					p: <p></p>,
				}
			) }
			indicator={ 'Creating...' }
		>
			{ ! existingAccounts && (
				<CreateAccounts
					googleAccount={ googleAccount }
					setAccounts={ setAccounts }
				/>
			) }
		</AccountCard>
	);
};

export default ConnectedGoogleComboAccountCard;
