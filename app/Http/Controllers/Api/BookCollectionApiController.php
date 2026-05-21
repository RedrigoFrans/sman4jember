<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookCollectionApiController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
        ]);

        $collections = DB::table('book_collections')
            ->where('nik', $request->nik)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                $bookData = json_decode($item->book_data, true) ?? [];

                return array_merge($bookData, [
                    'id' => $item->book_id,
                    'book_id' => $item->book_id,
                    'title' => $item->title,
                    'author' => $item->author,
                    'cover_image' => $item->cover_image,
                    'collection_type' => $item->collection_type ?? 'catalog',
                ]);
            });

        return response()->json([
            'status' => 200,
            'message' => 'Koleksi buku berhasil diambil',
            'data' => $collections,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'book_id' => 'required|string',
            'title' => 'required|string',
            'author' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'book_data' => 'nullable|array',
            'collection_type' => 'nullable|in:catalog,ebook',
        ]);

        $type = $request->collection_type ?? 'catalog';

        DB::table('book_collections')->updateOrInsert(
            [
                'nik' => $request->nik,
                'book_id' => $request->book_id,
                'collection_type' => $type,
            ],
            [
                'title' => $request->title,
                'author' => $request->author,
                'cover_image' => $request->cover_image,
                'book_data' => json_encode(array_merge(
                    $request->book_data ?? [],
                    ['collection_type' => $type]
                )),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'status' => 200,
            'message' => 'Buku berhasil ditambahkan ke koleksi',
        ]);
    }

    public function destroy(Request $request, $book_id)
    {
        $request->validate([
            'nik' => 'required|string',
            'collection_type' => 'nullable|in:catalog,ebook',
        ]);

        $type = $request->collection_type ?? 'catalog';

        DB::table('book_collections')
            ->where('nik', $request->nik)
            ->where('book_id', $book_id)
            ->where('collection_type', $type)
            ->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Buku berhasil dihapus dari koleksi',
        ]);
    }

    public function check(Request $request, $book_id)
    {
        $request->validate([
            'nik' => 'required|string',
        ]);

        $exists = DB::table('book_collections')
            ->where('nik', $request->nik)
            ->where('book_id', $book_id)
            ->exists();

        return response()->json([
            'status' => 200,
            'is_saved' => $exists,
        ]);
    }
}
