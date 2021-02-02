/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const SetupStepper = ( props ) => {
	const { className, ...rest } = props;

	return (
		<Stepper
			className={ classnames( 'gla-setup-stepper', className ) }
			{ ...rest }
		/>
	);
};

export default SetupStepper;
