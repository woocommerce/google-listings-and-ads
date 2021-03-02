/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const AppTextButton = ( props ) => {
	const { className, ...rest } = props;

	return (
		<Button
			className={ classnames( 'app-text-button', className ) }
			{ ...rest }
		/>
	);
};

export default AppTextButton;
