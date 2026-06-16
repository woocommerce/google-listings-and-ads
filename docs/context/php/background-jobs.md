# Background Jobs

GLA background processing runs via ActionScheduler, wrapped in a job hierarchy under `src/Jobs/`.

## Base Classes

- `AbstractActionSchedulerJob` — single-item processing
- `AbstractBatchedActionSchedulerJob` — paginates through a dataset; override `get_query()` and optionally `get_batch_size()`

## Creating a Job

```php
namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

class SyncMyData extends AbstractActionSchedulerJob {
    /** @var MyService */
    protected $service;

    public function __construct( ActionSchedulerInterface $scheduler, MyService $service ) {
        parent::__construct( $scheduler );
        $this->service = $service;
    }

    // Hook name becomes: gla/jobs/sync_my_data/process_item
    public function get_name(): string {
        return 'sync_my_data';
    }

    // Called by ActionScheduler; $items contains the args passed to schedule()
    public function process_items( array $items ): void {
        foreach ( $items as $item ) {
            $this->service->sync( $item );
        }
    }

    // Return false to prevent duplicate scheduling
    public function can_schedule( $args = [] ): bool {
        return ! $this->is_scheduled( $args );
    }
}
```

## Hook Naming

The ActionScheduler hook is derived from `get_name()`:

```
gla/jobs/{job_name}/process_item
```

`init()` in the base class registers this hook automatically — do not override `init()` without calling `parent::init()`.

## Lifecycle

```
1. SyncMyData::schedule( ['item1', 'item2'] )
   → action_scheduler->schedule_immediate( 'gla/jobs/sync_my_data/process_item', [items] )

2. ActionScheduler fires the hook
   → AbstractActionSchedulerJob::handle_process_items_action()
   → validates failure rate, attaches ActionSchedulerJobMonitor (timeout)
   → calls process_items( $items )

3. On exception: job is rescheduled immediately
```

## Interfaces

```php
// Cron-based recurring job
class MyRecurringJob extends AbstractActionSchedulerJob implements RecurringJobInterface {
    public function get_schedule(): string {
        return 'daily';  // ActionScheduler recurrence name
    }
}

// Triggered by a WordPress hook
class MyTriggeredJob extends AbstractActionSchedulerJob implements StartOnHookInterface {
    public function get_start_hook(): StartHook {
        return new StartHook( 'woocommerce_gla_some_event' );
    }
}
```

## Batched Jobs

```php
class SyncProducts extends AbstractBatchedActionSchedulerJob {
    public function get_name(): string {
        return 'sync_products';
    }

    // Return a QueryInterface that supports pagination
    protected function get_query(): QueryInterface {
        return $this->product_repository->get_syncable_products_query();
    }

    public function process_items( array $items ): void {
        $this->syncer->sync( $items );
    }
}
```

## Registration

Add to `src/Internal/DependencyManagement/JobServiceProvider.php`:

```php
protected $provides = [
    // ... existing ...
    SyncMyData::class => true,
];

public function register(): void {
    // ... existing ...
    $this->share( SyncMyData::class, MyService::class );
}
```

`JobInitializer` automatically calls `init()` on every registered job during plugin load — no additional wiring needed.
