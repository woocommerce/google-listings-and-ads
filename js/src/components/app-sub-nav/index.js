/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { NavigableMenu } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Navigation component that mimics the existing 3rd level navigation component written with jQuery.
 * Pre-configured to be used at "Reports" tab. Consists of "Programs" and "Products" links.
 * Relies on global `.subsubsub` class.
 *
 * @param {Object} props
 * @param {string} props.selectedKey Key of the selected tab.
 * @param {Array<Object>} props.tabs Array of tabs; each tab is an object `{ key, title, href }`.
 */
const AppSubNav = ( props ) => {
	const { selectedKey, tabs } = props;

	// Add bunch of spaces `' '` here and there to match jQuery implementation.
	return (
		<NavigableMenu
			role="tablist"
			orientation="horizontal"
			className="subsubsub gla-sub-nav"
		>
			{ tabs.map( ( tab, index ) => {
				const isCurrent = tab.key === selectedKey;

				return (
					<Fragment key={ tab.key }>
						<Link
							key={ tab.key }
							className={ classnames( {
								current: isCurrent,
								// Workaround for https://github.com/woocommerce/woocommerce-admin/issues/7772.
								'gla-sub-nav__item--current': isCurrent,
							} ) }
							tabIndex={ isCurrent ? null : -1 }
							id={ `${ tab.key }` }
							href={ tab.href }
							role="tab"
							aria-selected={ isCurrent }
							aria-controls={ `${ tab.key }-view` }
							aria-current={ isCurrent ? 'page' : false }
						>
							{ tab.title + ' ' }
						</Link>
						{ index < tabs.length - 1 ? ' | ' : ' ' }
					</Fragment>
				);
			} ) }
		</NavigableMenu>
	);
};

export default AppSubNav;
