<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Kelas;
use App\Services\MemberService;
use Illuminate\Http\Request;
use App\Imports\GuruImport;
use App\Imports\SiswaImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class MemberController extends Controller
{
    public function __construct(private MemberService $memberService) {}

    public function create()
    {
        $classes = Kelas::aktif()->orderBy('name')->get(['id', 'name']);
        return Inertia::render('Admin/Members/Create', [
            'classes' => $classes,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'             => 'required|in:siswa,guru,umum',
            'name'             => 'required|string|max:100',
            'nis_nip'          => 'nullable|string|max:30|unique:members,nis_nip',
            'class_id'         => 'required_if:type,siswa|nullable|exists:classes,id',
            'jenis_kelamin'    => 'nullable|in:L,P',
            'nisn'             => 'nullable|string|max:20',
            'tempat_lahir'     => 'nullable|string|max:100',
            'tanggal_lahir'    => 'nullable|date',
            'nik'              => 'nullable|string|max:20',
            'agama'            => 'nullable|string|max:30',
            'pangkat_golongan' => 'nullable|string|max:100',
            'address'          => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
        ]);

        // Wajib NIS/NIP hanya untuk siswa dan guru
        if (in_array($data['type'], ['siswa', 'guru']) && empty($data['nis_nip'])) {
            return back()->withErrors(['nis_nip' => 'NIS/NIP wajib diisi untuk tipe siswa atau guru.'])->withInput();
        }

        $this->memberService->createByAdmin($data, $request->user()->id);

        return redirect()->route('members.index')->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function update(Request $request, Member $member)
    {
        $data = $request->validate([
            'type'             => 'required|in:siswa,guru,umum',
            'name'             => 'required|string|max:100',
            'nis_nip'          => 'nullable|string|max:30|unique:members,nis_nip,' . $member->id,
            'class_id'         => 'required_if:type,siswa|nullable|exists:classes,id',
            'jenis_kelamin'    => 'nullable|in:L,P',
            'nisn'             => 'nullable|string|max:20',
            'tempat_lahir'     => 'nullable|string|max:100',
            'tanggal_lahir'    => 'nullable|date',
            'nik'              => 'nullable|string|max:20',
            'agama'            => 'nullable|string|max:30',
            'pangkat_golongan' => 'nullable|string|max:100',
            'address'          => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
        ]);

        // Wajib NIS/NIP hanya untuk siswa dan guru
        if (in_array($data['type'], ['siswa', 'guru']) && empty($data['nis_nip'])) {
            return back()->withErrors(['nis_nip' => 'NIS/NIP wajib diisi untuk tipe siswa atau guru.'])->withInput();
        }

        $member->update($data);

        return back()->with('success', "Data anggota {$member->name} berhasil diperbarui.");
    }

    public function destroy(Member $member)
    {
        if ($member->loans()->whereIn('status', ['aktif', 'diperpanjang', 'terlambat'])->exists()) {
            return back()->with('error', 'Tidak dapat menghapus anggota yang masih memiliki pinjaman aktif.');
        }

        $name = $member->name;
        
        \App\Models\AdminNotification::where('type', 'pendaftaran_anggota')
            ->where('message', 'like', "%{$name}%")
            ->update(['is_read' => true]);

        $member->delete();

        return back()->with('success', "Anggota {$name} berhasil dihapus.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
            'type' => 'required|in:siswa,guru',
        ]);

        try {
            if ($request->type === 'siswa') {
                Excel::import(new SiswaImport($request->user()->id), $request->file('file'));
            } else {
                Excel::import(new GuruImport($request->user()->id), $request->file('file'));
            }
            return back()->with('success', 'Data anggota berhasil diimpor.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }

    public function downloadTemplate($type)
    {
        if ($type === 'siswa') {
            $headers = ['No', 'Nama', 'KELAS', 'NIS', 'JK', 'NISN', 'Tempat Lahir', 'Tanggal Lahir', 'NIK', 'Agama', 'Alamat', 'HP'];
            $filename = 'template_import_siswa.csv';
        } else {
            $headers = ['NO', 'NAMA', 'NIP', 'Pangkat/Gol.Ruang', 'L/P'];
            $filename = 'template_import_guru.csv';
        }

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $members = Member::with(['user', 'kelas'])
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('member_code', 'like', "%{$s}%"))
            ->when($request->status,   fn($q, $s) => $q->where('status', $s))
            ->when($request->type,     fn($q, $t) => $q->where('type', $t))
            ->when($request->class_id, fn($q, $c) => $q->where('class_id', $c))
            ->when($request->type, fn($q) => $q->orderBy('name', 'asc'))
            ->when(!$request->type, fn($q) => $q->orderBy('id', 'desc'))
            ->paginate($perPage)->withQueryString();

        $typeCounts = Member::selectRaw('type, COUNT(*) as total')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('member_code', 'like', "%{$s}%"))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->class_id, fn($q, $c) => $q->where('class_id', $c))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        return Inertia::render('Admin/Members/Index', [
            'members'    => $members,
            'filters'    => $request->only(['search', 'status', 'type', 'class_id', 'per_page']),
            'classes'    => Kelas::aktif()->orderBy('name')->get(['id', 'name']),
            'typeCounts' => $typeCounts,
        ]);
    }

    public function show(Member $member)
    {
        $member->load(['user', 'kelas', 'loans.items.copy.book', 'loans.items.fines']);
        return Inertia::render('Admin/Members/Show', ['member' => $member]);
    }

    public function approve(Member $member, Request $request)
    {
        $this->memberService->approve($member, $request->user());
        return back()->with('success', "Anggota {$member->name} berhasil disetujui.");
    }

    public function reject(Member $member, Request $request)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $this->memberService->reject($member, $request->reason, $request->user());
        return back()->with('success', "Anggota {$member->name} ditolak.");
    }

    public function suspend(Member $member, Request $request)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $this->memberService->suspend($member, $request->reason);
        return back()->with('success', "Anggota {$member->name} disuspend.");
    }

    public function activate(Member $member)
    {
        $this->memberService->activate($member);
        return back()->with('success', "Anggota {$member->name} diaktifkan kembali.");
    }
}
