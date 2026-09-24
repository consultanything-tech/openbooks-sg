@php
    $obFlashes = [];
    foreach (['success', 'error', 'warning', 'info'] as $obType) {
        if (session($obType)) {
            $obFlashes[] = ['type' => $obType, 'message' => session($obType)];
        }
    }
    // Destructive actions can flash an undo target; the toast renders it as an action button.
    $obUndoUrl = session('undo_url');
    $obUndoLabel = session('undo_label', 'Undo');
@endphp

@if(count($obFlashes))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var flashes = @json($obFlashes);
            var undoUrl = @json($obUndoUrl);
            var undoLabel = @json($obUndoLabel);
            var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

            // Undo posts to the restore endpoint so it stays CSRF-protected.
            function postUndo() {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = undoUrl;
                form.style.display = 'none';
                var token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = csrf;
                form.appendChild(token);
                document.body.appendChild(form);
                form.submit();
            }

            flashes.forEach(function (flash, i) {
                var opts = { type: flash.type };
                if (i === 0 && undoUrl) {
                    opts.duration = 9000;
                    opts.actions = [{ label: undoLabel, onClick: postUndo }];
                }
                obToast(flash.message, opts);
            });
        });
    </script>
@endif
