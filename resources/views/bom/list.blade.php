@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Bill of Materials (BOM) Management</h4>
                <p class="text-muted small mb-0">Kelola BOM untuk Barang Jadi & Setengah Jadi.</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3">Part Number</th>
                                <th class="py-3">Part Name</th>
                                <th class="py-3">Category</th>
                                <th class="py-3 text-center">Status BOM</th>
                                <th class="py-3 text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $p)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $p->part_number }}</td>
                                <td>{{ $p->part_name }}</td>
                                <td>
                                    @if($p->category == 'FINISH GOOD')
                                        <span class="badge bg-primary">FINISH GOOD</span>
                                    @else
                                        <span class="badge bg-info text-dark">SEMI FINISH</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($p->bom_components_count > 0)
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">
                                            <i class="fas fa-check-circle me-1"></i> Terisi ({{ $p->bom_components_count }} Item)
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">
                                            <i class="fas fa-exclamation-circle me-1"></i> Kosong
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('bom.index', $p->id) }}" class="btn btn-sm btn-dark">
                                        <i class="fas fa-cogs me-1"></i> Kelola BOM
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Belum ada Part tipe FG/Semi-FG.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection