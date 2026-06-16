# Component Conventions

## Imports

Always import from `@wordpress/element`, never from `react`:

```js
// Correct
import { useState, useEffect, useCallback, lazy, createInterpolateElement } from '@wordpress/element';

// Wrong
import { useState } from 'react';
import React from 'react';
```

Import order: 1) external packages, 2) `@wordpress/*` and `@woocommerce/*`, 3) `~/` internal.

```js
/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import { Button } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { glaData } from '~/constants';
```

## Bundled vs. Externalized

`@wordpress/*` and `@woocommerce/*` packages are externalized — they're loaded from the host WP environment, not bundled. Never add them to `package.json` as production dependencies.

**Exception:** `@wordpress/dataviews` is bundled. Access it via the shim:

```js
// Correct — goes through the shim which loads the bundled version
import { DataViews } from '@wordpress/dataviews';

// The shim is loaded before the main bundle via glaData.dataViewsScriptUrl
```

All REST calls use `apiFetch` — never raw `fetch`:

```js
import apiFetch from '@wordpress/api-fetch';
// Or use store resolvers/actions which call apiFetch internally
```

## Design System Components

Shared primitives use the `app-*` prefix (located in `js/src/components/`):

| Component | Use For |
|---|---|
| `AppButton` | All buttons (wraps WC `Button`) |
| `AppModal` | Modal dialogs |
| `AppSpinner` | Loading states |
| `AppInputControl` | Text inputs |
| `AppSelectControl` | Dropdown selects |
| `AppTableCard` | Data tables |
| `AppDocumentationLink` | Links to GLA help docs |

High-usage: `AppButton` (110 imports), `Section` (45), `AppDocumentationLink` (37), `AdaptiveForm` (29).

`AdaptiveForm` provides form context with validation — use it for multi-field forms rather than managing form state manually.

## CSS

- Component CSS class prefix: `gla-` (e.g., `gla-campaign-table`, `gla-account-card__title`)
- SCSS files live next to the component (co-located)
- No `~` alias in SCSS files
- Do not manually import abstracts (`_colors`, `_variables`, `_mixins`, `_breakpoints`) — webpack auto-imports them

```scss
/* js/src/components/my-component/index.scss */
/* Abstracts are already available — no @import needed */
.gla-my-component {
    color: $color-primary;  // from _colors
    @include breakpoint-mobile { ... }  // from _breakpoints
}
```

## PropTypes and JSDoc

Use PropTypes for component prop documentation. For hooks and utility functions, use JSDoc `@param` and `@return`. Avoid multi-paragraph docstrings.
