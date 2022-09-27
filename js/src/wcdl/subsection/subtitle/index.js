/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const Subtitle = ( props ) => {
	const { className, ...rest } = props;

	return (
		<div
			className={ classnames( 'wcdl-subsection-subtitle', className ) }
			{ ...rest }
		/>
	);
};

export default Subtitle;
