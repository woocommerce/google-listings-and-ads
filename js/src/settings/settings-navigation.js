/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import getSelectedSettingsPageKey from './get-selected-settings-page-key';
import AppSubNav from '.~/components/app-sub-nav';
import { getSettingsUrl, pagePaths, subpaths } from '.~/utils/urls';

const tabs = [
	{
		key: pagePaths.settings,
		title: __( 'General', 'google-listings-and-ads' ),
		href: getSettingsUrl(),
	},
	{
		key: subpaths.attributeMapping,
		title: __( 'Attribute Mapping', 'google-listings-and-ads' ),
		href: getNewPath(
			{ subpath: subpaths.attributeMapping },
			'/google/settings',
			{}
		),
	},
];

/**
 * Component for rendering Settings Sub Nav
 *
 * @return {JSX.Element} The Settings Sub Nav
 */
const SettingsNavigation = () => {
	const settingsPageKeySelected = getSelectedSettingsPageKey();
	return <AppSubNav tabs={ tabs } selectedKey={ settingsPageKeySelected } />;
};

export default SettingsNavigation;
