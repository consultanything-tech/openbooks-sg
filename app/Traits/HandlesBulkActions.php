<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
 * Bulk row actions for index tables (select-all -> delete, with Undo).
 *
 * Deletion intentionally reuses the controller's single-record destroy()/restore()
 * so guards (e.g. "cannot delete a paid invoice"), counterparty balance side-effects
 * and activity logging stay identical to a one-off delete. Records are soft-deleted,
 * and the batch can be restored via the Undo action on the toast.
 */
trait HandlesBulkActions
{
    /** Fully-qualified model class handled by this controller's bulk actions. */
    abstract protected function bulkModelClass(): string;

    /** Index route name to redirect back to. */
    abstract protected function bulkIndexRoute(): string;

    /** Bulk-restore route name (target of the Undo action). */
    abstract protected function bulkRestoreRouteName(): string;

    public function bulkDelete(Request $request)
    {
        $ids = $this->validatedBulkIds($request);
        $class = $this->bulkModelClass();

        $deleted = 0;
        $skipped = 0;
        $deletedIds = [];

        foreach ($ids as $id) {
            $record = $class::find($id);
            if (! $record) {
                $skipped++;

                continue;
            }

            // Reuse the single-record path; ignore its redirect/flash.
            try {
                $this->destroy($id);
            } catch (\Throwable $e) {
                $skipped++;

                continue;
            }

            $fresh = $class::withTrashed()->find($id);
            if ($fresh && method_exists($fresh, 'trashed') && $fresh->trashed()) {
                $deleted++;
                $deletedIds[] = $id;
            } else {
                $skipped++;
            }
        }

        // destroy() flashed a per-record message; replace it with one summary.
        session()->forget(['success', 'error', 'info', 'undo_url', 'undo_label']);

        $redirect = redirect()->route($this->bulkIndexRoute());

        if ($deleted > 0) {
            session(['ob_bulk_undo_ids' => $deletedIds]);
            $msg = 'Deleted '.$deleted.' record'.($deleted === 1 ? '' : 's');
            if ($skipped > 0) {
                $msg .= ' ('.$skipped.' skipped)';
            }
            $redirect->with('success', $msg.'.')
                ->with('undo_url', route($this->bulkRestoreRouteName()))
                ->with('undo_label', 'Undo');
        } else {
            $redirect->with('error', 'None of the selected records could be deleted.');
        }

        return $redirect;
    }

    public function bulkRestore(Request $request)
    {
        $ids = (array) session('ob_bulk_undo_ids', []);
        session()->forget('ob_bulk_undo_ids');

        $class = $this->bulkModelClass();
        $restored = 0;

        foreach ($ids as $id) {
            $record = $class::withTrashed()->find($id);
            if ($record && method_exists($record, 'trashed') && $record->trashed()) {
                try {
                    $this->restore($id);
                    $restored++;
                } catch (\Throwable $e) {
                    // Best-effort restore; skip anything that no longer qualifies.
                }
            }
        }

        session()->forget(['success', 'error', 'info', 'undo_url', 'undo_label']);

        return redirect()->route($this->bulkIndexRoute())
            ->with('success', 'Restored '.$restored.' record'.($restored === 1 ? '' : 's').'.');
    }

    protected function validatedBulkIds(Request $request): array
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        return array_values(array_unique(array_map('intval', (array) $data['ids'])));
    }
}
