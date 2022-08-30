/**
 * Internal dependencies
 */
import NavigationClassic from '.~/components/navigation-classic';
import SettingsNavigation from '.~/settings/settings-navigation';

const SettingsHeader = () => {
	return (
		<>
			<NavigationClassic />
			<SettingsNavigation />
		</>
	);
};

export default SettingsHeader;
