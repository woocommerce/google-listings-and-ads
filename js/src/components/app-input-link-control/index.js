/**
 * External dependencies
 */
import { Icon, link as linkIcon } from '@wordpress/icons';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import AppInputControl from '.~/components/app-input-control';
import './index.scss';

/**
 * Renders an `AppInputControl` with a link icon prefix.
 *
 * @param {Object} props Props to be passed down to AppInputControl.
 */
const AppInputLinkControl = ( props ) => {
	const { className, ...rest } = props;

	return (
		<AppInputControl
			className={ classNames( 'app-input-link-control', className ) }
			prefix={ <Icon icon={ linkIcon } size={ 24 } /> }
			{ ...rest }
		/>
	);
};

export default AppInputLinkControl;
