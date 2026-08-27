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
			className="subsubsub gla-sub-nav"
			orientation="horizontal"
			role="tablist"
		>
			{ tabs.map( ( tab, index ) => {
				const isCurrent = tab.key === selectedKey;

				return (
					<Fragment key={ tab.key }>
						<Link
							aria-controls={ `${ tab.key }-view` }
							aria-current={ isCurrent ? 'page' : false }
							aria-selected={ isCurrent }
							className={ classnames( { current: isCurrent } ) }
							href={ tab.href }
							id={ `${ tab.key }` }
							role="tab"
							tabIndex={ isCurrent ? null : -1 }
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
