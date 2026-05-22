<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EbookApiController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->query('q', ''));
        $source = $request->query('source', 'all');
        $page = max(1, (int) $request->query('page', 1));

        $books = collect();
        $failedSources = [];

        $sources = [
            'internet_archive' => function () use ($query, $page) {
                return $this->searchInternetArchive($query, $page);
            },
            'google_books' => function () use ($query) {
                return $this->searchGoogleBooks($query);
            },
            'gutendex' => function () use ($query) {
                return $this->searchGutendex($query);
            },
            'open_library' => function () use ($query) {
                return $this->searchOpenLibrary($query);
            },
        ];

        foreach ($sources as $sourceName => $callback) {
            if ($source !== 'all' && $source !== $sourceName) {
                continue;
            }

            try {
                $books = $books->merge($callback());
            } catch (\Throwable $e) {
                $failedSources[] = $sourceName;

                Log::warning('Sumber ebook gagal diakses', [
                    'source' => $sourceName,
                    'query' => $query,
                    'message' => $e->getMessage(),
                ]);

                // Jangan hentikan semua data hanya karena 1 API luar timeout.
                continue;
            }
        }

        $books = $books
            ->filter(function ($book) {
                return !empty($book['id']) && !empty($book['title']);
            })
            ->unique(function ($book) {
                return strtolower(($book['source'] ?? '') . '-' . ($book['id'] ?? ''));
            })
            ->values();

        return response()->json([
            'status' => 200,
            'message' => empty($failedSources)
                ? 'Berhasil mengambil data ebook'
                : 'Berhasil mengambil data ebook, beberapa sumber sedang lambat',
            'failed_sources' => $failedSources,
            'data' => $books,
        ]);
    }

    private function searchInternetArchive(string $query, int $page)
    {
        if ($query === '') {
            $iaQuery = "mediatype:texts AND NOT collection:inlibrary AND NOT collection:printdisabled";
        } else {
            $iaQuery = "({$query}) AND mediatype:texts AND NOT collection:inlibrary AND NOT collection:printdisabled";
        }

        $response = Http::connectTimeout(5)->timeout(15)->retry(2, 300)->get('https://archive.org/advancedsearch.php', [
            'q' => $iaQuery,
            'output' => 'json',
            'rows' => 50,
            'page' => $page,
            'sort[]' => 'downloads desc',
        ]);

        if (!$response->successful()) {
            return collect([]);
        }

        $items = $response->json()['response']['docs'] ?? [];

        return collect($items)->map(function ($item) {
            $identifier = $item['identifier'] ?? '';

            $author = 'Tanpa Penulis';
            if (!empty($item['creator'])) {
                $author = is_array($item['creator'])
                    ? implode(', ', $item['creator'])
                    : $item['creator'];
            }

            $description = 'Tidak ada sinopsis untuk buku digital ini.';
            if (!empty($item['description'])) {
                $descStr = is_array($item['description'])
                    ? implode(' ', $item['description'])
                    : $item['description'];

                $description = Str::limit(strip_tags($descStr), 250);
            }

            return [
                'id' => $identifier,
                'source' => 'internet_archive',
                'title' => $item['title'] ?? 'Tanpa Judul',
                'author' => $author,
                'cover_image' => "https://archive.org/services/img/{$identifier}",
                'publisher' => $this->toText($item['publisher'] ?? '-'),
                'category' => [
                    'name' => 'E-Book (Internet Archive)',
                ],
                'synopsis' => $description,
                'pages' => (int) ($item['imagecount'] ?? 0),
                'stock' => 999,
                'web_reader' => $identifier
                    ? "https://archive.org/embed/{$identifier}?ui=embed"
                    : null,
                'pdf_link' => null,
            ];
        });
    }

    private function searchGoogleBooks(string $query)
    {
        $params = [
            'q' => $query === '' ? 'ebook' : $query,
            'maxResults' => 20,
            'printType' => 'books',
            'filter' => 'free-ebooks',
        ];

        // Kalau nanti kamu punya API key Google Books, bisa aktifkan ini:
        // $params['key'] = env('GOOGLE_BOOKS_API_KEY');

        $response = Http::connectTimeout(5)->timeout(10)->retry(2, 300)->get('https://www.googleapis.com/books/v1/volumes', $params);

        if (!$response->successful()) {
            return collect([]);
        }

        $items = $response->json()['items'] ?? [];

        return collect($items)->map(function ($item) {
            $volume = $item['volumeInfo'] ?? [];
            $access = $item['accessInfo'] ?? [];

            $id = $item['id'] ?? '';
            $title = $volume['title'] ?? 'Tanpa Judul';

            $authors = $volume['authors'] ?? [];
            $author = !empty($authors) ? implode(', ', $authors) : 'Tanpa Penulis';

            $cover = $volume['imageLinks']['thumbnail']
                ?? $volume['imageLinks']['smallThumbnail']
                ?? null;

            if ($cover) {
                $cover = str_replace('http://', 'https://', $cover);
            }

            $pdfLink = $access['pdf']['downloadLink'] ?? null;
            $webReader = $access['webReaderLink']
                ?? $volume['previewLink']
                ?? null;

            return [
                'id' => $id,
                'source' => 'google_books',
                'title' => $title,
                'author' => $author,
                'cover_image' => $cover,
                'publisher' => $volume['publisher'] ?? '-',
                'category' => [
                    'name' => 'E-Book (Google Books)',
                ],
                'synopsis' => isset($volume['description'])
                    ? Str::limit(strip_tags($volume['description']), 250)
                    : 'Tidak ada sinopsis untuk buku digital ini.',
                'pages' => (int) ($volume['pageCount'] ?? 0),
                'stock' => 999,
                'web_reader' => $webReader,
                'pdf_link' => $pdfLink,
            ];
        });
    }

    private function searchGutendex(string $query)
    {
        $response = Http::connectTimeout(5)->timeout(8)->retry(2, 300)->get('https://gutendex.com/books', [
            'search' => $query === '' ? 'book' : $query,
        ]);

        if (!$response->successful()) {
            return collect([]);
        }

        $items = $response->json()['results'] ?? [];

        return collect($items)->map(function ($item) {
            $id = $item['id'] ?? '';
            $formats = $item['formats'] ?? [];

            $authors = collect($item['authors'] ?? [])
                ->pluck('name')
                ->filter()
                ->values()
                ->all();

            $author = !empty($authors)
                ? implode(', ', $authors)
                : 'Tanpa Penulis';

            $cover = $formats['image/jpeg'] ?? null;

            $webReader = $formats['text/html']
                ?? $formats['text/html; charset=utf-8']
                ?? $formats['text/plain; charset=utf-8']
                ?? null;

            $epubLink = $formats['application/epub+zip'] ?? null;

            return [
                'id' => (string) $id,
                'source' => 'gutendex',
                'title' => $item['title'] ?? 'Tanpa Judul',
                'author' => $author,
                'cover_image' => $cover,
                'publisher' => 'Project Gutenberg',
                'category' => [
                    'name' => 'E-Book (Gutendex)',
                ],
                'synopsis' => !empty($item['subjects'])
                    ? Str::limit(implode(', ', $item['subjects']), 250)
                    : 'Tidak ada sinopsis untuk buku digital ini.',
                'pages' => 0,
                'stock' => 999,
                'web_reader' => $webReader,
                'pdf_link' => null,
                'epub_link' => $epubLink,
            ];
        });
    }

    private function searchOpenLibrary(string $query)
    {
        $response = Http::connectTimeout(5)->timeout(10)->retry(2, 300)->get('https://openlibrary.org/search.json', [
            'q' => $query === '' ? 'book' : $query,
            'limit' => 30,
        ]);

        if (!$response->successful()) {
            return collect([]);
        }

        $items = $response->json()['docs'] ?? [];

        return collect($items)->map(function ($item) {
            $key = $item['key'] ?? '';
            $openLibraryId = Str::afterLast($key, '/');

            // Aman: tidak semua buku punya field ia
            $iaId = null;

            if (!empty($item['ia']) && is_array($item['ia']) && !empty($item['ia'][0])) {
                $iaId = $item['ia'][0];
            }

            $cover = null;

            if (!empty($item['cover_i'])) {
                $cover = "https://covers.openlibrary.org/b/id/{$item['cover_i']}-L.jpg";
            } elseif ($iaId) {
                $cover = "https://archive.org/services/img/{$iaId}";
            }

            $author = 'Tanpa Penulis';

            if (!empty($item['author_name']) && is_array($item['author_name'])) {
                $author = implode(', ', array_slice($item['author_name'], 0, 3));
            }

            // Kalau punya ia, pakai Internet Archive agar bisa dibaca
            if ($iaId) {
                return [
                    'id' => $iaId,
                    'source' => 'internet_archive',
                    'title' => $item['title'] ?? 'Tanpa Judul',
                    'author' => $author,
                    'cover_image' => $cover,
                    'publisher' => !empty($item['publisher'])
                        ? $this->toText($item['publisher'])
                        : '-',
                    'category' => [
                        'name' => 'E-Book (Open Library / Internet Archive)',
                    ],
                    'synopsis' => !empty($item['subject'])
                        ? Str::limit($this->toText($item['subject']), 250)
                        : 'Tidak ada sinopsis untuk buku digital ini.',
                    'pages' => (int) ($item['number_of_pages_median'] ?? 0),
                    'stock' => 999,
                    'web_reader' => "https://archive.org/embed/{$iaId}?ui=embed",
                    'pdf_link' => null,
                ];
            }

            // Kalau tidak punya ia, tetap tampilkan sebagai metadata Open Library
            return [
                'id' => $openLibraryId,
                'source' => 'open_library',
                'title' => $item['title'] ?? 'Tanpa Judul',
                'author' => $author,
                'cover_image' => $cover,
                'publisher' => !empty($item['publisher'])
                    ? $this->toText($item['publisher'])
                    : '-',
                'category' => [
                    'name' => 'E-Book (Open Library)',
                ],
                'synopsis' => !empty($item['subject'])
                    ? Str::limit($this->toText($item['subject']), 250)
                    : 'Tidak ada sinopsis untuk buku digital ini.',
                'pages' => (int) ($item['number_of_pages_median'] ?? 0),
                'stock' => 999,
                'web_reader' => null,
                'pdf_link' => null,
            ];
        });
    }

    public function show($source, $externalId)
    {
        try {
            if ($source === 'internet_archive') {
                return $this->showInternetArchive($externalId);
            }

            if ($source === 'google_books') {
                return $this->showGoogleBooks($externalId);
            }

            if ($source === 'gutendex') {
                return $this->showGutendex($externalId);
            }

            if ($source === 'open_library') {
                return $this->showOpenLibrary($externalId);
            }

            return response()->json([
                'status' => 404,
                'message' => 'Source ebook tidak dikenali',
                'data' => null,
            ], 404);
        } catch (\Throwable $e) {
            Log::warning('Detail ebook gagal diakses', [
                'source' => $source,
                'external_id' => $externalId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 503,
                'message' => 'Sumber e-book sedang lambat atau tidak bisa diakses. Coba lagi beberapa saat.',
                'data' => null,
            ], 503);
        }
    }

    private function showInternetArchive($externalId)
    {
        $response = Http::connectTimeout(5)->timeout(15)->retry(2, 300)->get("https://archive.org/metadata/{$externalId}");

        if (!$response->successful()) {
            return response()->json([
                'status' => 404,
                'data' => null,
            ], 404);
        }

        $data = $response->json();
        $files = $data['files'] ?? [];
        $metadata = $data['metadata'] ?? [];

        $pdfFile = null;

        foreach ($files as $file) {
            $name = $file['name'] ?? '';
            $format = strtoupper($file['format'] ?? '');
            $size = (int) ($file['size'] ?? 0);

            if (
                str_contains($format, 'PDF') &&
                Str::endsWith(strtolower($name), '.pdf') &&
                !str_contains(strtolower($name), '/history/files/') &&
                !str_contains(strtolower($name), '.pdf.~') &&
                ($size === 0 || $size > 10000)
            ) {
                $pdfFile = $name;
                break;
            }
        }

        $pdfLink = null;

        if ($pdfFile) {
            $encodedFile = collect(explode('/', ltrim($pdfFile, '/')))
                ->map(function ($part) {
                    return rawurlencode($part);
                })
                ->implode('/');

            $pdfLink = "https://archive.org/download/{$externalId}/{$encodedFile}";
        }

        $pages = $this->archivePageCount($metadata, $files);

        $pageCount = 0;

        if (!empty($metadata['imagecount'])) {
            $pageCount = (int) $metadata['imagecount'];
        }

        if ($pageCount <= 0 && !empty($metadata['pages'])) {
            $pageCount = (int) $metadata['pages'];
        }

        if ($pageCount <= 0 && !empty($metadata['page_count'])) {
            $pageCount = (int) $metadata['page_count'];
        }
        return response()->json([
            'status' => 200,
            'data' => [
                'id' => $externalId,
                'source' => 'internet_archive',
                'title' => $this->toText($metadata['title'] ?? 'Tanpa Judul'),
                'author' => $this->toText($metadata['creator'] ?? 'Tanpa Penulis'),
                'publisher' => $this->toText($metadata['publisher'] ?? '-'),
                'synopsis' => Str::limit(strip_tags($this->toText($metadata['description'] ?? 'Tidak ada sinopsis.')), 250),
                'pages' => $pageCount,
                'page_count' => $pageCount,
                'pdf_link' => $pdfLink,
                'web_reader' => "https://archive.org/embed/{$externalId}?ui=embed",
                'cover_image' => "https://archive.org/services/img/{$externalId}",
            ],
        ]);
    }

    private function showGoogleBooks($externalId)
    {
        $response = Http::connectTimeout(5)->timeout(10)->retry(2, 300)->get("https://www.googleapis.com/books/v1/volumes/{$externalId}");

        if (!$response->successful()) {
            return response()->json([
                'status' => 404,
                'data' => null,
            ], 404);
        }

        $item = $response->json();
        $volume = $item['volumeInfo'] ?? [];
        $access = $item['accessInfo'] ?? [];

        $cover = $volume['imageLinks']['thumbnail']
            ?? $volume['imageLinks']['smallThumbnail']
            ?? null;

        if ($cover) {
            $cover = str_replace('http://', 'https://', $cover);
        }

        return response()->json([
            'status' => 200,
            'data' => [
                'id' => $externalId,
                'source' => 'google_books',
                'title' => $volume['title'] ?? 'Tanpa Judul',
                'author' => !empty($volume['authors'])
                    ? implode(', ', $volume['authors'])
                    : 'Tanpa Penulis',
                'publisher' => $volume['publisher'] ?? '-',
                'pages' => (int) ($volume['pageCount'] ?? 0),
                'page_count' => (int) ($volume['pageCount'] ?? 0),
                'synopsis' => isset($volume['description'])
                    ? Str::limit(strip_tags($volume['description']), 250)
                    : 'Tidak ada sinopsis.',
                'pdf_link' => $access['pdf']['downloadLink'] ?? null,
                'web_reader' => $access['webReaderLink'] ?? $volume['previewLink'] ?? null,
                'cover_image' => $cover,
            ],
        ]);
    }

    private function showGutendex($externalId)
    {
        $response = Http::connectTimeout(5)->timeout(8)->retry(2, 300)->get("https://gutendex.com/books/{$externalId}");

        if (!$response->successful()) {
            return response()->json([
                'status' => 404,
                'data' => null,
            ], 404);
        }

        $item = $response->json();
        $formats = $item['formats'] ?? [];

        $authors = collect($item['authors'] ?? [])
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'status' => 200,
            'data' => [
                'id' => (string) $externalId,
                'source' => 'gutendex',
                'title' => $item['title'] ?? 'Tanpa Judul',
                'author' => !empty($authors) ? implode(', ', $authors) : 'Tanpa Penulis',
                'publisher' => 'Project Gutenberg',
                'synopsis' => !empty($item['subjects'])
                    ? Str::limit(implode(', ', $item['subjects']), 250)
                    : 'Tidak ada sinopsis.',
                'pdf_link' => null,
                'web_reader' => $formats['text/html']
                    ?? $formats['text/html; charset=utf-8']
                    ?? $formats['text/plain; charset=utf-8']
                    ?? null,
                'epub_link' => $formats['application/epub+zip'] ?? null,
                'cover_image' => $formats['image/jpeg'] ?? null,
            ],
        ]);
    }

    private function showOpenLibrary($externalId)
    {
        $response = Http::connectTimeout(5)->timeout(10)->retry(2, 300)->get("https://openlibrary.org/works/{$externalId}.json");

        if (!$response->successful()) {
            return response()->json([
                'status' => 404,
                'data' => null,
            ], 404);
        }

        $item = $response->json();

        $cover = null;
        if (!empty($item['covers'][0])) {
            $cover = "https://covers.openlibrary.org/b/id/{$item['covers'][0]}-L.jpg";
        }

        $description = 'Tidak ada sinopsis.';
        if (!empty($item['description'])) {
            $description = is_array($item['description'])
                ? ($item['description']['value'] ?? 'Tidak ada sinopsis.')
                : $item['description'];
        }

        return response()->json([
            'status' => 200,
            'data' => [
                'id' => $externalId,
                'source' => 'open_library',
                'title' => $item['title'] ?? 'Tanpa Judul',
                'author' => 'Open Library',
                'publisher' => '-',
                'synopsis' => Str::limit(strip_tags($description), 250),
                'pdf_link' => null,
                'web_reader' => null,
                'cover_image' => $cover,
            ],
        ]);
    }

    private function archivePageCount(array $metadata, array $files): int
    {
        $candidates = [
            $metadata['imagecount'] ?? null,
            $metadata['pagecount'] ?? null,
            $metadata['pages'] ?? null,
            $metadata['page_count'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $value = $this->toInt($candidate);
            if ($value > 0) {
                return $value;
            }
        }

        // Beberapa item Archive menyimpan jumlah leaf di file metadata.
        foreach ($files as $file) {
            $name = strtolower($file['name'] ?? '');
            $format = strtolower($file['format'] ?? '');

            if (
                (str_contains($name, 'meta') || str_contains($format, 'metadata')) &&
                isset($file['pages'])
            ) {
                $value = $this->toInt($file['pages']);
                if ($value > 0) {
                    return $value;
                }
            }
        }

        return 0;
    }

    private function toInt($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $clean = trim($value);
            return is_numeric($clean) ? (int) $clean : 0;
        }

        return 0;
    }

    private function toText($value): string
    {
        if (is_array($value)) {
            return implode(', ', array_filter($value));
        }

        $text = trim((string) $value);

        return $text !== '' ? $text : '-';
    }
}
