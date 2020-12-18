/**
 * Internal dependencies
 */
import './index.scss';

const Title = ( props ) => {
	const { className, ...rest } = props;

	return (
		<div className={ `wcdl-subsection-title ${ className }` } { ...rest } />
	);
};

export default Title;
