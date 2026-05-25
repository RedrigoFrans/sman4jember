<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\BookCopy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $search       = $request->search;
        $categoryId   = $request->category_id;
        $availability = $request->availability; // tersedia | habis
        $sort         = $request->sort ?? 'terbaru';
        $perPage      = $request->per_page ?? 20;

        $books = Book::with('category')
            ->withCount([
                'copies as total_copies',
                'copies as available_copies' => fn($q) => $q->where('status', 'tersedia'),
                'copies as borrowed_copies'  => fn($q) => $q->where('status', 'dipinjam'),
            ])
            ->when($search, fn($q, $s) => $q->where(fn($q2) =>
                $q2->where('title',  'like', "%{$s}%")
                   ->orWhere('author', 'like', "%{$s}%")
                   ->orWhere('isbn',   'like', "%{$s}%")
            ))
            ->when($categoryId, fn($q, $c) => $q->where('category_id', $c))
            ->when($availability === 'tersedia', fn($q) => $q->whereHas('copies', fn($q2) => $q2->where('status', 'tersedia')))
            ->when($availability === 'habis',    fn($q) => $q->whereDoesntHave('copies', fn($q2) => $q2->where('status', 'tersedia')))
            ->when($sort === 'terpopuler', fn($q) => $q->orderByDesc('total_loans'))
            ->when($sort === 'judul',      fn($q) => $q->orderBy('title'))
            ->when($sort !== 'terpopuler' && $sort !== 'judul', fn($q) => $q->latest())
            ->paginate($perPage)
            ->withQueryString();

        $stats = [
            'total_books'     => Book::count(),
            'total_copies'    => BookCopy::count(),
            'available'       => BookCopy::where('status', 'tersedia')->count(),
            'borrowed'        => BookCopy::where('status', 'dipinjam')->count(),
        ];

        return Inertia::render('Admin/Books/Index', [
            'books'      => $books,
            'categories' => Category::orderBy('name')->get(),
            'filters'    => $request->only(['search', 'category_id', 'availability', 'sort', 'per_page']),
            'stats'      => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'isbn'        => 'nullable|string|max:20|unique:books',
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:200',
            'publisher'   => 'nullable|string|max:150',
            'year'        => 'nullable|digits:4',
            'edition'     => 'nullable|string|max:20',
            'language'    => 'nullable|string|max:30',
            'pages'       => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'rack_number' => 'nullable|string|max:20',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('cover_image')) {
            $file     = $request->file('cover_image');
            $filename = uniqid('cover_') . '.' . $file->getClientOriginalExtension();
            $path     = $file->storeAs('covers', $filename, 'public');
            $data['cover_image'] = $path;
        } else {
            unset($data['cover_image']);
        }

        $book = Book::create($data);
        return redirect()->route('books.index')->with('success', 'Buku berhasil ditambahkan.');
    }

    // Endpoint JSON untuk modal detail di halaman Index
    public function detail(Book $book)
    {
        $book->load(['category', 'copies' => fn($q) => $q->orderBy('copy_code')]);
        return response()->json($book);
    }

    // Halaman cetak label barcode
    public function printLabels(Request $request)
    {
        $search = $request->search;

        $copies = BookCopy::with('book.category')
            ->when($search, fn($q, $s) => $q->whereHas('book', fn($b) =>
                $b->where('title', 'like', "%{$s}%")
                  ->orWhere('author', 'like', "%{$s}%")
            ))
            ->orderBy('copy_code')
            ->get();

        return Inertia::render('Admin/Books/PrintLabels', [
            'copies'  => $copies,
            'search'  => $search,
            'total'   => $copies->count(),
        ]);
    }

    public function update(Request $request, Book $book)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'isbn'        => "nullable|string|max:20|unique:books,isbn,{$book->id}",
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:200',
            'publisher'   => 'nullable|string|max:150',
            'year'        => 'nullable|digits:4',
            'edition'     => 'nullable|string|max:20',
            'language'    => 'nullable|string|max:30',
            'pages'       => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'rack_number' => 'nullable|string|max:20',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_cover' => 'nullable|boolean',
        ]);

        // Hapus cover jika diminta
        if ($request->boolean('remove_cover') && $book->cover_image) {
            if (!str_starts_with($book->cover_image, 'http')) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $data['cover_image'] = null;
        } elseif ($request->hasFile('cover_image')) {
            // Hapus file lama jika bukan URL
            if ($book->cover_image && !str_starts_with($book->cover_image, 'http')) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $file     = $request->file('cover_image');
            $filename = uniqid('cover_') . '.' . $file->getClientOriginalExtension();
            $path     = $file->storeAs('covers', $filename, 'public');
            $data['cover_image'] = $path;
        } else {
            unset($data['cover_image']);
        }

        $book->update($data);
        return redirect()->route('books.index')->with('success', 'Data buku diperbarui.');
    }

    public function destroy(Book $book)
    {
        $book->delete();
        return redirect()->route('books.index')->with('success', 'Buku dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\BooksImport, $request->file('file'));
            return back()->with('success', 'Buku berhasil diimpor beserta eksemplarnya.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimpor buku: ' . $e->getMessage());
        }
    }

    // Tambah eksemplar fisik
    public function storeCopy(Request $request, Book $book)
    {
        $request->validate([
            'copy_code'  => 'required|string|max:30|unique:book_copies',
            'barcode'    => 'required|string|max:50|unique:book_copies',
            'condition'  => 'required|in:baik,rusak_ringan,rusak_berat,hilang',
        ]);

        $book->copies()->create($request->only(['copy_code', 'barcode', 'condition']));
        $book->increment('total_copies');

        return back()->with('success', 'Eksemplar berhasil ditambahkan.');
    }

    // Update status / kondisi eksemplar
    public function updateCopy(Request $request, BookCopy $copy)
    {
        $data = $request->validate([
            'condition' => 'required|in:baik,rusak_ringan,rusak_berat,hilang',
            'status'    => 'required|in:tersedia,dipinjam,tidak_aktif',
        ]);

        $copy->update($data);

        return back()->with('success', 'Eksemplar berhasil diperbarui.');
    }
}
