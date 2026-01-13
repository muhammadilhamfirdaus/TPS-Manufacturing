<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\Machine;
use Illuminate\Support\Facades\DB;

class MasterLineController extends Controller
{
    // 1. LIST LINE (INDEX)
    public function index()
    {
        $lines = ProductionLine::withCount('machines')->orderBy('name')->paginate(10);
        return view('master_line.index', compact('lines'));
    }

    // 2. FORM TAMBAH
    public function create()
    {
        return view('master_line.form');
    }

    // 3. FORM EDIT
    public function edit($id)
    {
        $line = ProductionLine::with('machines')->findOrFail($id);
        return view('master_line.form', compact('line'));
    }

    // 4. SIMPAN (STORE/UPDATE)
    // 4. SIMPAN (STORE/UPDATE)
    public function store(Request $request, $id = null)
    {
        // Validasi input
        $request->validate([
            'name' => 'required|string|max:100',
            'machines' => 'nullable|array',
            'machines.*.name' => 'required|string',
            'machines.*.machine_code' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // A. Simpan Production Line
            $dataLine = $request->only(['name']); 
            $dataLine['std_manpower'] = 0; // Default 0

            if ($id) {
                $line = ProductionLine::findOrFail($id);
                $line->update(['name' => $dataLine['name']]); 
            } else {
                $line = ProductionLine::create($dataLine);
            }

            // B. Simpan Mesin-Mesin
            $existingIds = [];

            if ($request->has('machines')) {
                foreach ($request->machines as $m) {
                    // Cek ID (Update / Create)
                    if (isset($m['id']) && $m['id']) {
                        $machine = Machine::find($m['id']);
                        if ($machine) {
                            $machine->update([
                                'name' => $m['name'],
                                'machine_code' => $m['machine_code'],
                                'machine_group' => $m['machine_group'] ?? null,
                                'production_line_id' => $line->id,
                                
                                // [PERBAIKAN DI SINI] Set default capacity 0
                                'capacity_per_hour' => 0 
                            ]);
                            $existingIds[] = $machine->id;
                        }
                    } else {
                        $newMachine = Machine::create([
                            'production_line_id' => $line->id,
                            'name' => $m['name'],
                            'machine_code' => $m['machine_code'],
                            'machine_group' => $m['machine_group'] ?? null,
                            
                            // [PERBAIKAN DI SINI] Set default capacity 0
                            'capacity_per_hour' => 0
                        ]);
                        $existingIds[] = $newMachine->id;
                    }
                }
            }

            // Hapus mesin yang dibuang user
            Machine::where('production_line_id', $line->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();

            DB::commit();
            return redirect()->route('master-line.index')->with('success', 'Data Line & Mesin berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors('Gagal menyimpan: ' . $e->getMessage());
        }
    }

    // 5. DELETE LINE
    public function destroy($id)
    {
        $line = ProductionLine::findOrFail($id);
        $line->machines()->delete(); // Hapus mesinnya dulu
        $line->delete(); // Hapus linenya
        
        return back()->with('success', 'Line Produksi berhasil dihapus.');
    }
}