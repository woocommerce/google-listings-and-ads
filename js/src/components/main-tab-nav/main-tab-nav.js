/**
 * Internal dependencies
 */
import AppTabNav from '~/components/app-tab-nav';
import useMenuEffect from '~/hooks/useMenuEffect';
import GtinMigrationBanner from '~/components/gtin-migration-banner';
import useMainTabs from './useMainTabs';

export { ALL_TABS } from './useMainTabs';

const MainTabNav = () => {
	useMenuEffect();

	const { tabs, selectedKey } = useMainTabs();

	return (
		<>
			<GtinMigrationBanner />
			<AppTabNav tabs={ tabs } selectedKey={ selectedKey } />
		</>
	);
};
export default MainTabNav;
