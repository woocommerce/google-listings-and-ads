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
} from '../components/google-combo-account-card/constants';

/**
 * Account creation in progress description.
 * @param {string|null} creatingWhich Which account is being created.
 * @return {Object} Text and subtext.
 */
const getAccountCreationTexts = ( creatingWhich ) => {
	let text = null;
	let subText = null;

	switch ( creatingWhich ) {
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

	return {
		text,
		subText,
	};
};

export default getAccountCreationTexts;
