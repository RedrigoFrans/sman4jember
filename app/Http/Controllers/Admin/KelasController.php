<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $query = Kelas::withCount('members');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->get('per_page', 10);
        $classes = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Kelas/Index', [
            'classes' => $classes,
            'filters' => $request->only('search', 'per_page'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:50|unique:classes,name',
            'grade'     => 'nullable|string|max:10',
            'major'     => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $data['grade'] = $this->parseGrade($data['grade'] ?? '');
        $data['major'] = $data['major'] ?? '';

        Kelas::create($data);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, Kelas $kela)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:50|unique:classes,name,' . $kela->id,
            'grade'     => 'nullable|string|max:10',
            'major'     => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $data['grade'] = $this->parseGrade($data['grade'] ?? '');
        $data['major'] = $data['major'] ?? '';

        $kela->update($data);

        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kela)
    {
        if ($kela->members()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus kelas yang masih memiliki anggota.');
        }

        $kela->delete();
        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    private function parseGrade($grade)
    {
        if (empty($grade)) return 0;
        if (is_numeric($grade)) return (int) $grade;

        $romans = [
            'I' => 1, 'V' => 5, 'X' => 10, 'L' => 50,
            'C' => 100, 'D' => 500, 'M' => 1000,
        ];

        $roman = strtoupper(trim($grade));
        $result = 0;

        for ($i = 0; $i < strlen($roman); $i++) {
            $char = $roman[$i];
            if (!isset($romans[$char])) {
                return 0; // Invalid roman character
            }
            $current = $romans[$char];
            $next = ($i + 1 < strlen($roman)) ? ($romans[$roman[$i + 1]] ?? 0) : 0;

            if ($current < $next) {
                $result -= $current;
            } else {
                $result += $current;
            }
        }

        return $result;
    }
}
