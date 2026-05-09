<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookApiController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Book::with('category', 'copies')
            ->withCount(['copies as available_count' => fn($q) => $q->where('status', 'tersedia')->where('condition', '!=', 'hilang')]);

        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('author', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->has('sort')) {
            if ($request->sort === 'Terbaru') {
                $query->latest();
            } else if ($request->sort === 'Populer') {
                $query->orderBy('total_loans', 'desc');
            } else {
                $query->latest();
            }
        } else {
            $query->latest();
        }

        $books = $query->paginate(18);

        $books->getCollection()->transform(function ($book) {
            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'cover_image' => $book->cover_image ? (str_starts_with($book->cover_image, 'http') ? $book->cover_image : asset($book->cover_image)) : null,
                'publisher' => $book->publisher,
                'category' => ['name' => $book->category?->name ?? 'Uncategorized'],
                'synopsis' => $book->description,
                'pages' => $book->pages ?? 0,
                'stock' => $book->available_count,
                'loan_count' => $book->total_loans ?? 0,
            ];
        });

        return response()->json($books);
    }

    public function show($id)
    {
        $book = \App\Models\Book::with('category', 'copies')
            ->withCount(['copies as available_count' => fn($q) => $q->where('status', 'tersedia')->where('condition', '!=', 'hilang')])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'cover_image' => $book->cover_image ? (str_starts_with($book->cover_image, 'http') ? $book->cover_image : asset($book->cover_image)) : null,
                'publisher' => $book->publisher,
                'category' => ['name' => $book->category?->name ?? 'Uncategorized'],
                'synopsis' => $book->description,
                'pages' => $book->pages ?? 0,
                'stock' => $book->available_count,
                'loan_count' => $book->total_loans ?? 0,

                'copies' => $book->copies->map(function ($copy) {
                    return [
                        'copy_code' => $copy->copy_code,
                        'barcode' => $copy->barcode,
                        'condition' => $copy->condition,
                        'status' => $copy->status,
                    ];
                }),
            ]
        ]);
    }
}
