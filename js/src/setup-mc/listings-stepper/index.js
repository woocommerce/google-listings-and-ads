/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import useGetOption from '.~/hooks/useGetOption';
import SavedListingsStepper from './saved-listings-stepper';

const ListingsStepper = () => {
	const { loading, data: savedStep } = useGetOption(
		'gla_setup_mc_saved_step'
	);

	// when loading is done, savedStep could still be undefined for a short moment,
	// so we check for it and show AppSpinner to prevent Step 1 flickered on screen.
	if ( loading || savedStep === undefined ) {
		return <AppSpinner />;
	}

	// if there is no savedStep in the database,
	// savedStep is returned as `false` from the API call.
	return <SavedListingsStepper savedStep={ savedStep || '1' } />;
};

export default ListingsStepper;
