<?php

namespace App\Imports;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BooksImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        $defaultCategory = Category::firstOrCreate(['name' => 'Non Fiksi', 'code' => 'NONFIKSI']);

        foreach ($rows as $collectionRow) {
            $row = $collectionRow->toArray();
            // Jika baris kosong atau tidak ada judul
            if (empty($row['judul_buku'])) {
                continue; 
            }

            $classificationNumber = $row['no_klasifikasi'] ?? null;
            
            $category = $defaultCategory;
            if (!empty($row['kategori'])) {
                $catName = trim($row['kategori']);
                $category = Category::firstOrCreate([
                    'name' => $catName,
                    'code' => Str::upper(substr(Str::slug($catName, ''), 0, 10))
                ]);
            } elseif ($classificationNumber) {
                // Menggunakan teks klasifikasi awal sebagai kategori
                $catName = 'Klas ' . $classificationNumber;
                $category = Category::firstOrCreate([
                    'name' => $catName,
                    'code' => substr(Str::upper($classificationNumber), 0, 10)
                ]);
            }

            // Parsing Tanggal Diterima
            $receivedDate = null;
            if (!empty($row['diterima_tanggal'])) {
                if (is_numeric($row['diterima_tanggal'])) {
                    $receivedDate = Date::excelToDateTimeObject($row['diterima_tanggal'])->format('Y-m-d');
                } else {
                    $receivedDate = date('Y-m-d', strtotime(str_replace('/', '-', $row['diterima_tanggal'])));
                }
            }

            // Identifikasi buku berdasarkan ISBN (jika ada) dan Judul+Penulis
            $bookQuery = Book::query();
            if (!empty($row['isbn'])) {
                $bookQuery->where('isbn', $row['isbn']);
            } else {
                $bookQuery->where('title', $row['judul_buku'])->where('author', $row['penulis']);
            }
            $book = $bookQuery->first();

            if (!$book) {
                $book = Book::create([
                    'category_id'           => $category->id,
                    'classification_number' => $classificationNumber,
                    'title'                 => $row['judul_buku'] ?? 'Tanpa Judul',
                    'author'                => $row['penulis'] ?? 'Unknown',
                    'publisher'             => $row['penerbit'] ?? null,
                    'city'                  => $row['kota'] ?? null,
                    'year'                  => $row['tahun_terbit'] ?? null,
                    'isbn'                  => $row['isbn'] ?? null,
                    'acquisition_type'      => $row['perolehan'] ?? null,
                    'received_date'         => $receivedDate,
                    'inventory_year'        => $row['tahun'] ?? null,
                    'description'           => $row['sinopsis'] ?? null,
                ]);
            }

            // Insert Eksemplar Buku
            $jmlBuku = isset($row['jml_buku']) ? (int) $row['jml_buku'] : 1;
            
            for ($i = 0; $i < $jmlBuku; $i++) {
                $copyIndex = BookCopy::where('book_id', $book->id)->count() + 1;
                $copyCode = 'BK-' . str_pad($book->id, 4, '0', STR_PAD_LEFT) . '-C' . $copyIndex;

                // Generate barcode unik — ulangi jika ternyata sudah ada di database
                do {
                    $barcode = 'BC' . random_int(10000000, 99999999);
                } while (BookCopy::where('barcode', $barcode)->exists());

                BookCopy::create([
                    'book_id'   => $book->id,
                    'copy_code' => $copyCode,
                    'barcode'   => $barcode,
                    'condition' => 'baik',
                    'status'    => 'tersedia',
                    'notes'     => 'Impor Excel'
                ]);
            }
        }
    }
}
