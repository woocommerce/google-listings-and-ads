/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import useAdsCampaignsMissingEuDeclaration from '~/hooks/useAdsCampaignsMissingEuDeclaration';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';

/**
 * Component that checks for campaigns missing the EU political declaration and triggers
 * the modal via context. Renders nothing — it is a side-effect-only component for
 * auto-detection. The modal itself is rendered by `EuPoliticalDeclarationProvider`.
 *
 * @return {null} Always returns null.
 */
const EuPoliticalDeclaration = () => {
	const {
		data: { continents },
		hasFinishedResolution: hasResolvedCountriesAndContinents,
	} = useAppSelectDispatch( 'getMCCountriesAndContinents' );
	const { data: campaignsMissingEuDeclaration, loaded } =
		useAdsCampaignsMissingEuDeclaration();
	const { showModal } = useEuPoliticalDeclarationContext();

	useEffect( () => {
		if (
			! loaded ||
			! hasResolvedCountriesAndContinents ||
			! campaignsMissingEuDeclaration?.length
		) {
			return;
		}

		const euCountries = continents.EU?.countries || [];
		const campaignsTargetingEu = campaignsMissingEuDeclaration.filter(
			( campaign ) =>
				campaign.targeted_locations?.some( ( location ) =>
					euCountries.includes( location )
				)
		);

		if ( campaignsTargetingEu.length ) {
			showModal();
		}
	}, [
		loaded,
		hasResolvedCountriesAndContinents,
		campaignsMissingEuDeclaration,
		continents,
		showModal,
	] );

	return null;
};

export default EuPoliticalDeclaration;
