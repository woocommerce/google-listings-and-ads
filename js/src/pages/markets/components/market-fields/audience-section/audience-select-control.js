/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useMCCountries from '~/hooks/useMCCountries';
import SupportedCountrySelect from '~/components/supported-country-select';
import useMarkets from '../../../hooks/useMarkets';
import './audience-select-control.scss';

/**
 * Component for editing the primary market's audience (countries) in the Edit Market modal.
 */
const AudienceSelectControl = () => {
	const {
		getInputProps,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();
	const { data: markets, hasFinishedResolution: hasResolvedMarkets } =
		useMarkets();
	const { data: mcCountries, hasFinishedResolution: hasResolvedCountries } =
		useMCCountries();

	// A country belonging to a secondary market cannot also be in the primary audience, so it
	// is not offered here. Primary carries no country of its own, so it filters itself out.
	// Both markets and supported countries have to be resolved first — otherwise a country
	// another market owns would briefly show as available before markets loads.
	const countryCodes = useMemo( () => {
		if ( ! hasResolvedMarkets || ! hasResolvedCountries || ! mcCountries ) {
			return undefined;
		}

		const ownedCountries = new Set(
			( markets ?? [] )
				.filter( ( market ) => market.country )
				.map( ( market ) => market.country )
		);

		return Object.keys( mcCountries ).filter(
			( code ) => ! ownedCountries.has( code )
		);
	}, [ markets, hasResolvedMarkets, mcCountries, hasResolvedCountries ] );

	const inputProps = getInputProps( 'countries' );

	return (
		<div className="gla-audience-select-control">
			<SupportedCountrySelect
				{ ...inputProps }
				countryCodes={ countryCodes }
				help={ __(
					'Select which countries your store ships to.',
					'google-listings-and-ads'
				) }
				label={ __( 'Audience', 'google-listings-and-ads' ) }
				multiple
			/>

			{ renderRequestedValidation( 'countries' ) }
		</div>
	);
};

export default AudienceSelectControl;
