/**
 * External dependencies
 */
import classnames from 'classnames';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a Notice component with extra props.
 *
 * It supports all the props from @wordpress/components - Notice Component
 *
 * ## Usage
 *
 * ```jsx
 * <AppNotice >
 * 		My Notice
 * </AppButton>
 * ```
 *
 * @param {*} props Props to be forwarded to {@link Notice}.
 */
const AppNotice = ( props ) => {
	const { className, ...rest } = props;
	const classes = [ 'app-notice', className ];
	return <Notice className={ classnames( ...classes ) } { ...rest } />;
};

export default AppNotice;
