/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';
import EmptyMetricsGraphic from '~/images/price-benchmark/empty-metrics.svg';
import './index.scss';

const EmptyMetricsNotice = () => {
	return (
		<div className="gla-price-benchmark__empty-metrics">
			<div className="gla-price-benchmark__empty-metrics-graphic">
				<img
					src={ EmptyMetricsGraphic }
					alt={ __(
						'No sale price suggestions graphic',
						'google-listings-and-ads'
					) }
					width={ 88 }
					height={ 88 }
				/>
			</div>
			<div className="gla-price-benchmark__empty-metrics-description">
				<p>
					{ __(
						'You do not have any sale price suggestions at this moment.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'<a>Find out</a> if you meet all eligibility criteria to receive suggestions in the future.',
							'google-listings-and-ads'
						),
						{
							a: (
								<AppDocumentationLink
									className="gla-empty-metrics-notice__link"
									href="https://support.google.com/merchants/answer/13798101"
									context="price-benchmark-empty-metrics-notice"
									linkId="empty-metric-notice-find-out-link"
								/>
							),
						}
					) }
				</p>
			</div>
		</div>
	);
};

export default EmptyMetricsNotice;
