/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const AppTableCardDiv = ( props ) => {
	const { className, ...rest } = props;

	return (
		<div
			className={ classnames( 'app-table-card-div', className ) }
			{ ...rest }
		></div>
	);
};

export default AppTableCardDiv;
