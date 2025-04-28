/**
 * Internal dependencies
 */
import useLayout from '~/hooks/useLayout';
import SetupTopBar from './setup-top-bar';
import SetupStepper from './setup-stepper';

/**
 * The entry page component of the Onboarding flow.
 *
 * It's also the former `SetupMC` page component.
 */
const Onboarding = () => {
	useLayout( 'full-page' );

	return (
		<>
			<SetupTopBar />
			<SetupStepper />
		</>
	);
};

export default Onboarding;
