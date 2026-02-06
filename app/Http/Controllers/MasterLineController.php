<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\Machine;
use App\Models\ActivityLog; // <--- Import Model Log
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // <--- Import Auth
// Pastikan Import Class di atas
use App\Exports\LineMachineTemplateExport;
use App\Imports\LinesImport;
use Maatwebsite\Excel\Facades\Excel;


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
        return view('master_line.form'); 
    }

    // 3. FORM EDIT
    public function edit($id)
    {
        $line = ProductionLine::with('machines')->findOrFail($id);
        
        return view('master_line.form', compact('line')); 
    }

    // 4. SIMPAN (STORE/UPDATE)
    public function store(Request $request, $id = null)
    {
        // Validasi input
        $request->validate([
            'plant' => 'required|string', 
            'name'  => 'required|string|max:100',
            'total_shifts' => 'required|integer|min:1|max:3', 
            
            // Validasi Array Mesin
            'machines' => 'nullable|array',
            'machines.*.name' => 'required|string',
            'machines.*.machine_code' => 'required|string',
            'machines.*.type' => 'nullable|string|in:INTERNAL,SUBCONT', 
        ]);

        DB::beginTransaction();
        try {
            // A. Simpan Production Line
            $dataLine = $request->only(['plant', 'name', 'total_shifts']); 
            
            // Set default std_manpower jadi 0 (dihitung di MPP)
            $dataLine['std_manpower'] = 0; 

            // 
            if ($id) {
                $line = ProductionLine::findOrFail($id);
                $line->update($dataLine); 
                $logAction = 'UPDATE MASTER LINE';
                $logDesc = "Update Line: {$line->name} (Plant: {$line->plant})";
            } else {
                $line = ProductionLine::create($dataLine);
                $logAction = 'CREATE MASTER LINE';
                $logDesc = "Create New Line: {$line->name} (Plant: {$line->plant})";
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
                        'type' => $m['type'] ?? 'INTERNAL', 
                        'production_line_id' => $line->id,
                        'capacity_per_hour' => 0 
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

            // [TAMBAHAN] CATAT LOG CREATE/UPDATE
            ActivityLog::create([
                'user_id'     => Auth::id(),
                'user_name'   => Auth::user()->name,
                'action'      => $logAction,
                'description' => $logDesc
            ]);

            DB::commit();
           return redirect()->route('lines.index')->with('success', 'Data Berhasil Disimpan');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors('Gagal menyimpan: ' . $e->getMessage());
        }
    }

    // 5. DELETE LINE
    public function destroy($id)
    {
        try {
            $line = ProductionLine::findOrFail($id);
            $lineInfo = "{$line->plant} - {$line->name}";
            
            $line->machines()->delete(); // Hapus mesinnya dulu
            $line->delete(); // Hapus linenya
            
            // [TAMBAHAN] CATAT LOG DELETE
            ActivityLog::create([
                'user_id'     => Auth::id(),
                'user_name'   => Auth::user()->name,
                'action'      => 'DELETE MASTER LINE',
                'description' => "Menghapus Line Produksi: {$lineInfo}"
            ]);

            return back()->with('success', 'Line Produksi berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    // 1. DOWNLOAD TEMPLATE
    public function downloadTemplate()
    {
        return Excel::download(new LineMachineTemplateExport, 'template_line_machine.xlsx');
    }

    // 2. IMPORT EXCEL
   public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new LinesImport, $request->file('file'));
            return back()->with('success', 'Data Line & Machine berhasil diimport!');

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
             $failures = $e->failures();
             dd($failures); // Cek validasi excel
             
        } catch (\Exception $e) {
            // ==============================================
            // MODE DEBUGGING: AKTIF (HAPUS NANTI JIKA SUDAH FIX)
            // ==============================================
            dd([
                'STATUS' => 'ERROR IMPORT LINE & MACHINE',
                'PESAN' => $e->getMessage(), // <--- Ini yang paling penting
                'FILE' => $e->getFile(),
                'LINE' => $e->getLine()
            ]);
        }
    }
}