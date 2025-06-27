/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import AppButton from '~/components/app-button';
import usePreference from '~/hooks/usePreference';
import bannerImageURL from '~/images/price-benchmark/price-benchmark-banner-icon.svg';
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import './index.scss';

const BANNER_DISMISSED_KEY = 'price-benchmark-banner-dismissed';

const Banner = () => {
	const { set } = useDispatch( preferencesStore );
	const isDismissed = usePreference( BANNER_DISMISSED_KEY );

	const handleDismiss = () => {
		set( PREFERENCES_STORE_NAMESPACE, BANNER_DISMISSED_KEY, true );
	};

	if ( isDismissed ) {
		return null;
	}

	return (
		<Card size="large" className="gla-price-benchmark-suggestions-banner">
			<CardBody className="gla-price-benchmark-suggestions-banner__body">
				<div className="gla-price-benchmark-suggestions-banner__graphic">
					<img src={ bannerImageURL } alt="" width={ 164 } />
				</div>

				<div className="gla-price-benchmark-suggestions-banner__text">
					<Badge>{ __( 'New', 'google-listings-and-ads' ) }</Badge>

					<h3>
						{ __(
							'Price Benchmark & Suggestions',
							'google-listings-and-ads'
						) }
					</h3>
				</div>

				<div className="gla-price-benchmark-suggestions-banner__footer">
					<p>
						{ createInterpolateElement(
							__(
								'This report includes a competitive pricing analysis, price recommendations, and insights like <strong>Effectiveness</strong> to help you identify opportunities, compare against competitors, and accelerate your sales growth.',
								'google-listings-and-ads'
							),
							{
								strong: <strong />,
							}
						) }
					</p>
					<AppButton variant="secondary" onClick={ handleDismiss }>
						{ __( 'Dismiss', 'google-listings-and-ads' ) }
					</AppButton>
				</div>
			</CardBody>
		</Card>
	);
};

export default Banner;
