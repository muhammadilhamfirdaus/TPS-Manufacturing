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
        // Urutkan berdasarkan Plant lalu Nama Line
        $lines = ProductionLine::withCount('machines')
                    ->orderBy('plant')
                    ->orderBy('name')
                    ->paginate(10);
                    
        return view('master_line.index', compact('lines'));
    }

    // 2. FORM TAMBAH
    public function create()
    {
        // PERBAIKAN: Panggil 'master_line.form' (sesuai nama file Anda)
        return view('master_line.form'); 
    }

    // 3. FORM EDIT
    public function edit($id)
    {
        $line = ProductionLine::with('machines')->findOrFail($id);
        
        // PERBAIKAN: Panggil 'master_line.form'
        return view('master_line.form', compact('line')); 
    }

    // 4. SIMPAN (STORE/UPDATE)
    public function store(Request $request, $id = null)
    {
        // Validasi input
        $request->validate([
            'plant' => 'required|string', 
            'name'  => 'required|string|max:100',
            'total_shifts' => 'required|integer|min:1|max:3', // Validasi Shift
            
            // Validasi Array Mesin
            'machines' => 'nullable|array',
            'machines.*.name' => 'required|string',
            'machines.*.machine_code' => 'required|string',
            'machines.*.type' => 'nullable|string|in:INTERNAL,SUBCONT', // Validasi Tipe Mesin
        ]);

        DB::beginTransaction();
        try {
            // A. Simpan Production Line
            $dataLine = $request->only(['plant', 'name', 'total_shifts']); 
            
            // Set default std_manpower jadi 0 (dihitung di MPP)
            $dataLine['std_manpower'] = 0; 

            if ($id) {
                $line = ProductionLine::findOrFail($id);
                $line->update($dataLine); 
            } else {
                $line = ProductionLine::create($dataLine);
            }

            // B. Simpan Mesin-Mesin
            $existingIds = [];

            if ($request->has('machines')) {
                foreach ($request->machines as $m) {
                    // Siapkan data mesin
                    $machineData = [
                        'name' => $m['name'],
                        'machine_code' => $m['machine_code'],
                        'machine_group' => $m['machine_group'] ?? null,
                        'type' => $m['type'] ?? 'INTERNAL', // Default INTERNAL
                        'production_line_id' => $line->id,
                        'capacity_per_hour' => 0 // Default 0
                    ];

                    // Cek ID (Update / Create)
                    if (isset($m['id']) && $m['id']) {
                        $machine = Machine::find($m['id']);
                        if ($machine) {
                            $machine->update($machineData);
                            $existingIds[] = $machine->id;
                        }
                    } else {
                        $newMachine = Machine::create($machineData);
                        $existingIds[] = $newMachine->id;
                    }
                }
            }

            // Hapus mesin yang dibuang user dari form
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