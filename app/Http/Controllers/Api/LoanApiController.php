<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LoanApiController extends Controller
{
    public function index(Request $request)
    {
        $member = $request->user()->member;

        if (!$member) {
            return response()->json(['data' => []]);
        }

        $items = \App\Models\LoanItem::with(['loan', 'copy.book'])
            ->whereHas('loan', function($q) use ($member) {
                $q->where('member_id', $member->id);
            })
            ->latest()
            ->get();

        $formatted = $items->map(function ($item) {
            $book = $item->copy?->book;
            $dueDate = $item->loan?->effectiveDueDate();
            return [
                'id' => $item->id,
                'loan_id' => $item->loan_id,
                'status' => $item->isReturned() ? 'selesai' : 'aktif',
                'due_date' => $dueDate ? \Carbon\Carbon::parse($dueDate)->translatedFormat('d M Y') : '-',
                'return_date' => $item->returned_at ? \Carbon\Carbon::parse($item->returned_at)->translatedFormat('d M Y') : '-',
                'book' => [
                    'title' => $book?->title ?? 'Buku Tidak Diketahui',
                    'author' => $book?->author ?? '-',
                    'cover_image' => $book && $book->cover_image ? (str_starts_with($book->cover_image, 'http') ? $book->cover_image : Storage::url($book->cover_image)) : null,
                ]
            ];
        });

        return response()->json(['data' => $formatted]);
    }
}
