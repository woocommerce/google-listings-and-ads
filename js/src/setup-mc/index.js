/**
 * Internal dependencies
 */
import useLayout from '.~/hooks/useLayout';
import useUpdateRestAPIAuthorizeStatusByUrlQuery from '.~/hooks/useUpdateRestAPIAuthorizeStatusByUrlQuery';
import SetupMCTopBar from './top-bar';
import SetupStepper from './setup-stepper';

const SetupMC = () => {
	useLayout( 'full-page' );
	useUpdateRestAPIAuthorizeStatusByUrlQuery();

	return (
		<>
			<SetupMCTopBar />
			<SetupStepper />
		</>
	);
};

export default SetupMC;
