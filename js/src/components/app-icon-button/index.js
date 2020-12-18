/**
 * External dependencies
 */
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const AppIconButton = ( props ) => {
	const { icon, text, className = '', ...rest } = props;

	return (
		<Button className={ `app-icon-button ${ className }` } { ...rest }>
			<div>{ icon }</div>
			{ text }
		</Button>
	);
};

export default AppIconButton;
