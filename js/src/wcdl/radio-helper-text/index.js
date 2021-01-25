/**
 * Internal dependencies
 */
import './index.scss';

const RadioHelperText = ( props ) => {
	const { className = '', ...rest } = props;

	return (
		<span
			className={ `wcdl-radio-helper-text ${ className }` }
			{ ...rest }
		/>
	);
};

export default RadioHelperText;
