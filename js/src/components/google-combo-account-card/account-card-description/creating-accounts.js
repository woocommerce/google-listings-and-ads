/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	CREATING_ADS_ACCOUNT,
	CREATING_BOTH_ACCOUNTS,
	CREATING_MC_ACCOUNT,
} from '../constants';

/**
 * Account creation in progress description.
 * @param {Object} props Component props.
 * @param {string|null} props.creatingAccounts Whether the accounts are being created. Possible values are: 'both', 'ads', 'mc'.
 * @return {JSX.Element|null} JSX markup.
 */
const CreatingAccounts = ( { creatingAccounts } ) => {
	let text = null;
	let subText = null;

	if ( ! creatingAccounts ) {
		return null;
	}

	switch ( creatingAccounts ) {
		case CREATING_BOTH_ACCOUNTS:
			text = __(
				'You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you.',
				'google-listings-and-ads'
			);
			subText = __(
				'Merchant Center is required to sync products so they show on Google. Google Ads is required to set up conversion measurement for your store.',
				'google-listings-and-ads'
			);
			break;

		case CREATING_ADS_ACCOUNT:
			text = __(
				'You don’t have Google Ads account, so we’re creating one for you.',
				'google-listings-and-ads'
			);
			subText = __(
				'Required to set up conversion measurement for your store.',
				'google-listings-and-ads'
			);
			break;

		case CREATING_MC_ACCOUNT:
			text = __(
				'You don’t have Merchant Center account, so we’re creating one for you.',
				'google-listings-and-ads'
			);
			subText = __(
				'Required to sync products so they show on Google.',
				'google-listings-and-ads'
			);
			break;
	}

	return (
		<>
			<p>{ text }</p>
			<em>{ subText }</em>
		</>
	);
};

export default CreatingAccounts;
