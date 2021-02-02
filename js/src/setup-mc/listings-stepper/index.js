/**
 * Internal dependencies
 */
import AppSpinner from '../../components/app-spinner';
import useGetOption from '../../hooks/useGetOption';
import SavedListingsStepper from './saved-listings-stepper';
import './index.scss';

const ListingsStepper = () => {
	const { loading, data: savedStep } = useGetOption(
		'gla_setup_mc_saved_step'
	);

	if ( loading ) {
		return <AppSpinner />;
	}

	return <SavedListingsStepper savedStep={ savedStep || '1' } />;
};

export default ListingsStepper;
