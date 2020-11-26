/**
 * External dependencies
 */
import { NavigableMenu } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const tabs = [
	{
		name: 'dashboard',
		title: 'Dashboard',
		className: 'tab-dashboard',
		path: '/google/dashboard',
	},
	{
		name: 'analytics',
		title: 'Analytics',
		className: 'tab-analytics',
		path: '/google/analytics',
	},
	{
		name: 'product-feed',
		title: 'Product Feed',
		className: 'tab-product-fee',
		path: '/google/product-feed',
	},
	{
		name: 'settings',
		title: 'Settings',
		className: 'tab-settings',
		path: '/google/settings',
	},
];

const TabLink = ( { tabId, path, children, selected, ...rest } ) => {
	return (
		<Link
			role="tab"
			tabIndex={ selected ? null : -1 }
			aria-selected={ selected }
			id={ tabId }
			href={ 'admin.php?page=wc-admin&path=' + path }
			{ ...rest }
		>
			{ children }
		</Link>
	);
};

const TabNav = ( props ) => {
	const { initialName } = props;
	const activeClass = 'is-active';

	return (
		<div className="gla-page-tabs-component">
			<NavigableMenu
				role="tablist"
				orientation="horizontal"
				className="components-tab-panel__tabs"
			>
				{ tabs.map( ( tab ) => (
					<TabLink
						className={ classnames(
							'components-button',
							'components-tab-panel__tabs-item',
							tab.className,
							{
								[ activeClass ]: tab.name === initialName,
							}
						) }
						tabId={ `${ tab.name }` }
						aria-controls={ `${ tab.name }-view` }
						selected={ tab.name === initialName }
						key={ tab.name }
						path={ tab.path }
					>
						{ tab.title }
					</TabLink>
				) ) }
			</NavigableMenu>
		</div>
	);
};

export default TabNav;
