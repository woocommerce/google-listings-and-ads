# Tours and Guides

GLA has two systems for guided UI: **TourKit** spotlight tours (step-by-step highlights) and **GuideModal** multi-page what's-new guides.

## TourKit Tours

Uses `TourKit` from `@woocommerce/components`. Tour state is persisted via the GLA store → `OptionsInterface::TOURS` in PHP.

### useTour Hook

```js
import useTour from '~/hooks/useTour';

const MY_TOUR_ID = 'my-feature-tour';  // unique string, define as constant in the component file

const MyTour = () => {
    const { tourChecked, setTourChecked } = useTour( MY_TOUR_ID );

    // Always return null when the tour has been seen/dismissed
    if ( tourChecked ) {
        return null;
    }

    return (
        <TourKit
            config={ {
                placement: 'bottom',
                steps: [
                    {
                        referenceElements: { desktop: '.gla-my-feature-button' },
                        meta: {
                            name: 'step-one',
                            heading: __( 'New feature', 'google-listings-and-ads' ),
                            descriptions: { desktop: __( 'Description here.', 'google-listings-and-ads' ) },
                        },
                    },
                ],
                options: { effects: { spotlight: { interactivity: { enabled: false } } } },
            } }
            onFinish={ () => setTourChecked( true ) }
            onDismiss={ () => setTourChecked( true ) }
        />
    );
};
```

### Active Tours

Located in `js/src/components/tours/`:
- `RebrandingTour` — `'rebranding-tour'`
- `CampaignAssetsTour` — `'campaign-assets-tour'`
- `YouTubeShoppingTour` — `'youtube-shopping-tour'`

### CSS Convention

Tour-specific classes use the tour name as prefix: `gla-{tour-name}-tour__*`

```scss
.gla-rebranding-tour__header { ... }
.gla-campaign-assets-tour__step-icon { ... }
```

## GuideModal / Guide

For multi-page "what's new" modals. Uses `Guide` from `@wordpress/components`.

```js
import { Guide } from '@wordpress/components';
import { GUIDE_NAMES } from '~/constants';

// Guide names are defined as constants
// GUIDE_NAMES.SUBMISSION_SUCCESS, GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS, etc.

// Open via URL query param
// ?guide=submission-success → GuidesPage reads the query and renders the appropriate Guide
```

Guide visibility is controlled by the `guide` query parameter, not by the store. The `GuidesPage` component in the dashboard reads `getQuery().guide` and conditionally renders the correct guide.

## Key Differences

| | TourKit | GuideModal |
|---|---|---|
| Persistence | Store → PHP options | URL query param |
| UI style | Spotlight overlay on specific elements | Full-screen modal with pages |
| Dismissal | `setTourChecked(true)` | Navigate away or close button |
| Use for | Feature discovery tooltips | Multi-step onboarding/success messages |
