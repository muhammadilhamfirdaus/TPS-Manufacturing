@extends('layouts.app_simple')

@section('content')

    {{-- Tambahkan SortableJS --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>

    <style>
        .handle {
            cursor: grab;
            color: #adb5bd;
        }

        .handle:active {
            cursor: grabbing;
            color: #495057;
        }

        .sortable-ghost {
            background-color: #e9ecef !important;
            opacity: 0.8;
        }

        /* Style Select2 */
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #dee2e6;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>

    <div class="row justify-content-center">
        <div class="col-12">

            {{-- HEADER --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Bill of Materials (BOM)</h4>
                    <p class="text-muted small mb-0">Kelola komposisi material untuk produk jadi.</p>
                </div>
                <a href="{{ route('bom.list') }}" class="btn btn-light border shadow-sm text-secondary btn-sm px-3">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <div class="row">
                {{-- KOLOM KIRI: INFO PARENT PRODUCT --}}
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                @if($parent->photo)
                                    <img src="{{ asset('storage/' . $parent->photo) }}" class="rounded-circle shadow-sm"
                                        style="width: 100px; height: 100px; object-fit: cover;">
                                @else
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                        style="width: 80px; height: 80px;">
                                        <i class="fas fa-cube fa-3x"></i>
                                    </div>
                                @endif
                            </div>

                            <h5 class="fw-bold text-dark mb-1">{{ $parent->part_name }}</h5>
                            <span class="badge bg-dark px-3 py-2 rounded-pill mb-3">{{ $parent->part_number }}</span>

                            <div class="text-start bg-light p-3 rounded-3 mt-3">
                                <h6 class="small fw-bold text-secondary text-uppercase border-bottom pb-2 mb-2">Detail
                                    Informasi</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Code Part</span>
                                    <span class="fw-bold text-primary">{{ $parent->code_part }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Customer</span>
                                    <span class="fw-bold text-dark">{{ $parent->customer ?? '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Kategori</span>
                                    <span class="fw-bold text-dark">{{ $parent->category }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KOLOM KANAN: FORM & LIST KOMPONEN --}}
                <div class="col-md-8">

                    {{-- CARD 1: FORM TAMBAH --}}
                    <div class="card shadow-sm border-0 rounded-4 mb-4">
                        <div class="card-header bg-white py-3 border-bottom-0">
                            <h6 class="fw-bold text-success mb-0"><i class="fas fa-plus me-2"></i>Tambah Komponen</h6>
                        </div>
                        <div class="card-body pt-0">
                            <form action="{{ route('bom.store', $parent->id) }}" method="POST"
                                class="row g-3 align-items-end">
                                @csrf
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold text-muted">Pilih Part / Material</label>
                                    <select name="child_product_id" class="form-select select2" required>
                                        <option value="">-- Cari Part Number / Name --</option>
                                        @foreach($allProducts as $p)
                                            <option value="{{ $p->id }}">
                                                {{ $p->code_part }} - {{ $p->part_name }} ({{ $p->part_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Qty Usage</label>
                                    <div class="input-group">
                                        <input type="number" step="any" name="quantity" class="form-control"
                                            placeholder="0.00000001" required>
                                        <span class="input-group-text bg-light text-muted small">Pcs</span>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success w-100 fw-bold">Add</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- CARD 2: LIST KOMPONEN (SORTABLE) --}}
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white py-3 border-bottom-0">
                            <h6 class="fw-bold text-dark mb-0">Daftar Komponen (Child Parts)</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-secondary small text-uppercase">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">#</th> {{-- Handle --}}
                                            <th class="ps-4">Code Part</th>
                                            <th>Part Name</th>
                                            <th>Part Number</th>
                                            <th class="text-center">Qty Usage</th>
                                            <th class="text-center" style="width: 150px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bom-list">
                                        {{-- NOTE: Gunakan $bomDetails dari Controller yang sudah diurutkan by Sequence --}}
                                        @forelse($bomDetails as $item)
                                            <tr data-id="{{ $item->id }}">
                                                {{-- 1. DRAG HANDLE --}}
                                                <td class="text-center"><i class="fas fa-grip-vertical handle"></i></td>

                                                <td class="ps-4 fw-bold text-primary">{{ $item->childProduct->code_part }}</td>
                                                <td>{{ $item->childProduct->part_name }}</td>
                                                <td class="text-muted small">{{ $item->childProduct->part_number }}</td>

                                                {{-- 2. QTY DISPLAY --}}
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border px-3 py-2"
                                                        style="font-size: 13px;">
                                                        {{ (float) $item->quantity }}
                                                    </span>
                                                </td>

                                                {{-- 3. AKSI --}}
                                                <td class="text-center">
                                                    {{-- Tombol Edit --}}
                                                    <button type="button"
                                                        class="btn btn-sm btn-warning text-white me-1 btn-edit"
                                                        data-id="{{ $item->id }}" data-qty="{{ (float) $item->quantity }}"
                                                        data-name="{{ $item->childProduct->part_name }}">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>

                                                    {{-- Tombol Hapus --}}
                                                    <form
                                                        action="{{ route('bom.destroy', ['id' => $parent->id, 'childId' => $item->id]) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Hapus komponen ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger text-white">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i><br>
                                                    Belum ada komponen dalam BOM ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="editBomModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white py-2">
                    <h6 class="modal-title fw-bold small"><i class="fas fa-edit me-1"></i> Edit Quantity</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditBom" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="small text-muted">Part Name</label>
                            <input type="text" id="editPartName" class="form-control form-control-sm bg-light" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">Qty Usage</label>
                            <input type="number" name="quantity" id="editQty" step="any"
                                class="form-control text-center fw-bold fs-5" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">SIMPAN PERUBAHAN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPT: Sortable & Edit Modal --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // 1. INIT DRAG & DROP (SORTABLE)
            const el = document.getElementById('bom-list');
            if (el) {
                new Sortable(el, {
                    handle: '.handle', // Hanya bisa drag via icon grip
                    animation: 150,
                    ghostClass: 'sortable-ghost',

                    onEnd: function (evt) {
                        let order = [];
                        // Ambil urutan baru dari DOM
                        document.querySelectorAll('#bom-list tr').forEach((row, index) => {
                            if (row.getAttribute('data-id')) {
                                order.push({
                                    id: row.getAttribute('data-id'),
                                    sequence: index + 1
                                });
                            }
                        });

                        // Kirim ke Server via AJAX
                        fetch("{{ route('bom.reorder') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({ order: order })
                        })
                            .then(response => response.json())
                            .then(data => console.log('Urutan Tersimpan'))
                            .catch(error => console.error('Error:', error));
                    }
                });
            }

            // 2. LOGIC MODAL EDIT
            const editModalEl = document.getElementById('editBomModal');
            if (editModalEl) {
                const editModal = new bootstrap.Modal(editModalEl);
                const formEdit = document.getElementById('formEditBom');
                const inputName = document.getElementById('editPartName');
                const inputQty = document.getElementById('editQty');

                document.querySelectorAll('.btn-edit').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const id = this.getAttribute('data-id');
                        const qty = this.getAttribute('data-qty');
                        const name = this.getAttribute('data-name');

                        inputName.value = name;
                        inputQty.value = qty;

                        // Set Action URL dinamis: /bom-management/{id}/update
                        formEdit.action = `/bom-management/${id}/update`;

                        editModal.show();
                    });
                });
            }
        });
    </script>

@endsection