/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const HelperText = ( props ) => {
	const { className, children } = props;

	return (
		<div
			className={ classnames( 'wcdl-subsection-helper-text', className ) }
		>
			{ children }
		</div>
	);
};

export default HelperText;
