# Event Tracking

GLA uses WooCommerce Tracks for analytics. All events go through GLA's wrapper to automatically attach base properties.

## Recording Events

```js
import { recordGlaEvent, queueRecordGlaEvent } from '~/utils/tracks';

// Immediate — fires synchronously
recordGlaEvent( 'gla_campaign_save_click', {
    id: campaignId,
    budget,
} );

// Queued — fires after navigation completes (use for events on links/navigation)
queueRecordGlaEvent( 'gla_dashboard_campaign_click', { id: campaignId } );
```

## Automatic Base Properties

Every event recorded via `recordGlaEvent` automatically receives:

| Property | Value |
|---|---|
| `gla_version` | Plugin version from `glaData.initialWpData.version` |
| `gla_mc_id` | Merchant Center ID (omitted if 0) |
| `gla_ads_id` | Google Ads account ID (omitted if 0) |

These are added by `addBaseEventProperties()` called during store initialization. Never add them manually.

## Event Naming

Pattern: `gla_{noun}_{verb}`

```
gla_modal_open
gla_modal_close
gla_campaign_create_button_click
gla_table_page_click
gla_budget_input_change
gla_onboarding_completed
```

Always prefix with `gla_`. Do not use `wc_` or bare names.

## Multi-Step Flow Helpers

```js
import { recordStepperChangeEvent, recordStepContinueEvent } from '~/utils/tracks';

// When moving between steps
recordStepperChangeEvent( 'gla_setup', 'target-audience' );

// When user clicks "Continue" on a step
recordStepContinueEvent( 'gla_setup', 'target-audience' );
```

## Store-Level Events

Events fired from resolvers/actions use the `GLA_RECORD_DATA_EVENT` store control — see [data-store-controls.md](data-store-controls.md).

## Filter-Based Extension

```js
import { addFilter } from '@wordpress/hooks';
import { FILTER_ONBOARDING } from '~/utils/tracks';

// Extend event properties for onboarding events
addFilter( FILTER_ONBOARDING, 'my-namespace', ( eventData ) => {
    return { ...eventData, extra_prop: 'value' };
} );
```

`FILTER_BUDGET_RECOMMENDATIONS` is another available filter for budget recommendation events.

## When to Use queueRecordGlaEvent

Use `queueRecordGlaEvent` when the event fires on a button/link that navigates away:

```js
// Navigation happens immediately; the event might be lost if fired synchronously
<AppButton onClick={ () => queueRecordGlaEvent( 'gla_link_click', { url } ) } href={ url } />
```

The queued event fires after the next page render, ensuring it is not lost during navigation.
