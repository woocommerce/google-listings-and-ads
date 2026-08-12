/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppSubNav from '~/components/app-sub-nav';

export const SETTINGS_SECTIONS = {
	GENERAL: 'general',
	ACCOUNTS: 'accounts',
};

const TABS = [
	{
		key: SETTINGS_SECTIONS.GENERAL,
		title: __( 'General', 'google-listings-and-ads' ),
	},
	{
		key: SETTINGS_SECTIONS.ACCOUNTS,
		title: __( 'Accounts', 'google-listings-and-ads' ),
	},
];

/**
 * Resolves the currently selected settings section from the URL query,
 * defaulting to the General section.
 *
 * @return {string} The selected section key.
 */
export function getSelectedSection() {
	const { section } = getQuery();
	return section === SETTINGS_SECTIONS.ACCOUNTS
		? SETTINGS_SECTIONS.ACCOUNTS
		: SETTINGS_SECTIONS.GENERAL;
}

/**
 * Secondary navigation for the Settings page, splitting it into the General
 * and Accounts subtabs. Selection is driven by the `section` URL query so the
 * subtabs are linkable and survive reloads. Uses the same third-level
 * navigation styling as the Reports page.
 *
 * @return {JSX.Element} The settings subtab navigation.
 */
export default function SettingsNav() {
	const selectedKey = getSelectedSection();

	const tabs = TABS.map( ( tab ) => ( {
		...tab,
		href: getNewPath( { section: tab.key }, '/google/settings', {} ),
	} ) );

	return <AppSubNav tabs={ tabs } selectedKey={ selectedKey } />;
}
