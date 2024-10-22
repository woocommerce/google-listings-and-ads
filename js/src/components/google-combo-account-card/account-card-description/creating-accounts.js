/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAccountCreationContext } from '../account-creation-context';
import {
	CREATING_ADS_ACCOUNT,
	CREATING_BOTH_ACCOUNTS,
	CREATING_MC_ACCOUNT,
} from '../constants';

/**
 * Account creation in progress description.
 * @return {JSX.Element|null} JSX markup.
 */
const CreatingAccounts = () => {
	const creatingAccounts = useAccountCreationContext();

	if ( ! creatingAccounts ) {
		return null;
	}

	const helperText = {
		text: null,
		subText: null,
	};

	switch ( creatingAccounts ) {
		case CREATING_BOTH_ACCOUNTS:
			helperText.text = __(
				'You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you.',
				'google-listings-and-ads'
			);
			helperText.subText = __(
				'Merchant Center is required to sync products so they show on Google. Google Ads is required to set up conversion measurement for your store.',
				'google-listings-and-ads'
			);
			break;

		case CREATING_ADS_ACCOUNT:
			helperText.text = __(
				'You don’t have Google Ads account, so we’re creating one for you.',
				'google-listings-and-ads'
			);
			helperText.subText = __(
				'Required to set up conversion measurement for your store.',
				'google-listings-and-ads'
			);
			break;

		case CREATING_MC_ACCOUNT:
			helperText.text = __(
				'You don’t have Merchant Center account, so we’re creating one for you.',
				'google-listings-and-ads'
			);
			helperText.subText = __(
				'Required to sync products so they show on Google.',
				'google-listings-and-ads'
			);
			break;
	}

	const { text, subText } = helperText;

	return (
		<>
			<p>{ text }</p>
			<em>{ subText }</em>
		</>
	);
};

export default CreatingAccounts;
