@extends('layouts.app')

@section('title', 'Scan Receipt')

@section('content')
<div class="space-y-6">
    <x-sticky-form-bar :cancel-url="route('expense_claims.index')">
        <x-slot:title>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <i data-lucide="camera" class="w-4 h-4 text-amber-500"></i> Scan Receipt
                    @if($hasAi)
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                        <i data-lucide="zap" class="w-4 h-4 mr-0.5"></i> AI-Powered
                    </span>
                    @endif
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Upload a receipt image and let AI extract the details automatically</p>
            </div>
        </x-slot:title>
    </x-sticky-form-bar>

    <!-- Upload Zone -->
    <div id="uploadSection" class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-8">
        <div id="dropZone" class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-12 text-center cursor-pointer hover:border-amber-400 dark:hover:border-amber-500 transition-colors group">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center mb-4 group-hover:bg-amber-100 dark:group-hover:bg-amber-500/20 transition">
                <i data-lucide="camera" class="w-6 h-6 text-amber-500"></i>
            </div>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Drop your receipt image here</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">or click to browse -- JPEG, PNG, WebP up to 10MB</p>
            <input type="file" id="receiptFileInput" accept="image/*" capture="environment" class="hidden">
            <button type="button" onclick="document.getElementById('receiptFileInput').click()" class="btn btn-secondary">
                <i data-lucide="upload" aria-hidden="true"></i> Choose Image
            </button>
        </div>
        <div id="uploadProgress" class="hidden mt-4">
            <div class="flex items-center gap-3 text-xs text-slate-600 dark:text-slate-400">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-amber-500"></i>
                <span id="uploadStatusText">Uploading and extracting receipt data...</span>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 mt-2">
                <div id="progressBar" class="bg-amber-500 h-1.5 rounded-full transition-all duration-300" style="width: 10%"></div>
            </div>
        </div>
    </div>

    <!-- Results Section (hidden until upload completes) -->
    <div id="resultsSection" class="hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Image Preview -->
            <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">Receipt Preview</h3>
                <img id="receiptPreview" src="" alt="Receipt preview" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 object-contain max-h-[500px] bg-slate-50 dark:bg-slate-800">
                <div id="confidenceBadge" class="mt-3 flex items-center gap-2">
                    <!-- Populated by JS -->
                </div>
            </div>

            <!-- Extracted Fields Form -->
            <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">Extracted Details</h3>

                <div id="aiMessage" class="hidden mb-3 p-3 rounded-xl text-xs"></div>

                <form id="receiptForm" method="POST" action="{{ route('receipt_ocr.create') }}">
                    @csrf
                    <input type="hidden" name="receipt_path" id="formReceiptPath" value="">

                    <!-- Record Type Toggle -->
                    <div class="mb-4">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">Create As</label>
                        <div class="flex rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="record_type" value="expense_claim" checked class="hidden peer">
                                <div class="peer-checked:bg-indigo-600 peer-checked:text-white text-center py-2 text-xs font-medium text-slate-600 dark:text-slate-400 transition">
                                    <i data-lucide="banknote" class="w-4 h-4 mr-1"></i> Expense Claim
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="record_type" value="bill" class="hidden peer">
                                <div class="peer-checked:bg-indigo-600 peer-checked:text-white text-center py-2 text-xs font-medium text-slate-600 dark:text-slate-400 transition border-l border-slate-200 dark:border-slate-700">
                                    <i data-lucide="receipt" class="w-4 h-4 mr-1"></i> Vendor Bill
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Merchant -->
                    <div class="mb-3">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">Merchant / Vendor *</label>
                        <input type="text" name="merchant" id="fieldMerchant" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500" placeholder="Store or vendor name">
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">Receipt Date *</label>
                        <input type="date" name="receipt_date" id="fieldDate" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                    </div>

                    <!-- Amount + GST -->
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">Total Amount *</label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="fieldAmount" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500" placeholder="0.00">
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">GST Amount</label>
                            <input type="number" step="0.01" min="0" name="gst_amount" id="fieldGst" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500" placeholder="0.00">
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="mb-3">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">Category</label>
                        <select data-combobox name="category_id" id="fieldCategory" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">Notes</label>
                        <textarea name="notes" id="fieldNotes" rows="2" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 resize-none" placeholder="Additional notes..."></textarea>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-3">
                        <button type="submit" id="primarySubmit" class="btn btn-primary">
                            <i data-lucide="save" aria-hidden="true"></i> Save
                        </button>
                        <button type="button" onclick="resetScan()" class="btn btn-secondary">
                            <i data-lucide="undo-2" aria-hidden="true"></i> Scan Another
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('receiptFileInput');
    const uploadSection = document.getElementById('uploadSection');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const statusText = document.getElementById('uploadStatusText');
    const resultsSection = document.getElementById('resultsSection');

    // Drag and drop
    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.add('border-amber-400', 'bg-amber-50/50', 'dark:bg-amber-500/5'); });
    });
    ['dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.remove('border-amber-400', 'bg-amber-50/50', 'dark:bg-amber-500/5'); });
    });
    dropZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length > 0) handleFile(files[0]);
    });
    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) handleFile(fileInput.files[0]);
    });

    function handleFile(file) {
        if (!file.type.match(/^image\/(jpeg|jpg|png|webp)$/)) {
            alert('Please upload a JPEG, PNG, or WebP image.');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert('Image must be under 10MB.');
            return;
        }
        uploadFile(file);
    }

    function uploadFile(file) {
        uploadProgress.classList.remove('hidden');
        progressBar.style.width = '10%';
        statusText.textContent = 'Uploading receipt image...';

        const formData = new FormData();
        formData.append('receipt_image', file);
        formData.append('_token', '{{ csrf_token() }}');

        // Simulate progress
        let progress = 10;
        const progressInterval = setInterval(() => {
            progress = Math.min(progress + Math.random() * 15, 85);
            progressBar.style.width = progress + '%';
        }, 300);

        fetch('{{ route("receipt_ocr.upload") }}', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            clearInterval(progressInterval);
            progressBar.style.width = '100%';
            statusText.textContent = 'Extraction complete!';

            setTimeout(() => {
                uploadProgress.classList.add('hidden');
                progressBar.style.width = '10%';

                if (!data.success) {
                    alert(data.message || 'Upload failed.');
                    return;
                }

                populateResults(data);
            }, 500);
        })
        .catch(err => {
            clearInterval(progressInterval);
            uploadProgress.classList.add('hidden');
            alert('Upload error: ' + err.message);
        });
    }

    function populateResults(data) {
        const d = data.data;

        // Show preview
        const preview = document.getElementById('receiptPreview');
        preview.src = URL.createObjectURL(fileInput.files[0] || new Blob());
        // Use the local file for preview since storage URL may not be publicly accessible
        const files = fileInput.files;
        if (files && files[0]) {
            preview.src = URL.createObjectURL(files[0]);
        }

        // Populate fields
        document.getElementById('fieldMerchant').value = d.merchant || '';
        document.getElementById('fieldDate').value = d.date || new Date().toISOString().split('T')[0];
        document.getElementById('fieldAmount').value = d.amount || '';
        document.getElementById('fieldGst').value = d.gst_amount != null ? d.gst_amount : '';
        document.getElementById('fieldNotes').value = (d.items && d.items.length > 0)
            ? 'Items: ' + d.items.map(i => i.description + ' (' + (i.quantity || 1) + 'x ' + (i.unit_price || 0).toFixed(2) + ')').join(', ')
            : '';
        document.getElementById('formReceiptPath').value = data.storage_path || '';

        // Confidence badge
        const conf = Math.round((d.confidence || 0) * 100);
        const confColor = conf >= 80 ? 'emerald' : conf >= 50 ? 'amber' : 'red';
        document.getElementById('confidenceBadge').innerHTML =
            '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-' + confColor + '-100 dark:bg-' + confColor + '-500/15 text-' + confColor + '-700 dark:text-' + confColor + '-400 border border-' + confColor + '-200 dark:border-' + confColor + '-500/20">' +
            '<i data-lucide="bot" class="w-4 h-4 mr-1"></i> AI Confidence: ' + conf + '%</span>' +
            '<span class="text-[10px] text-slate-400">Method: ' + (d.method || 'unknown') + '</span>';

        // AI message
        const msgEl = document.getElementById('aiMessage');
        if (d.message) {
            msgEl.classList.remove('hidden');
            const isError = d.method === 'error' || d.method === 'fallback';
            msgEl.className = 'mb-3 p-3 rounded-xl text-xs ' + (isError
                ? 'bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-700 dark:text-amber-400'
                : 'bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400');
            msgEl.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-1"></i> ' + d.message;
        }

        // Show results, hide upload
        resultsSection.classList.remove('hidden');
        uploadSection.classList.add('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    window.resetScan = function() {
        resultsSection.classList.add('hidden');
        uploadSection.classList.remove('hidden');
        fileInput.value = '';
        document.getElementById('receiptForm').reset();
        document.getElementById('aiMessage').classList.add('hidden');
    };
})();
</script>
@endsection
