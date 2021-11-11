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
