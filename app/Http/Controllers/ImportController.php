<?php

namespace App\Http\Controllers;

use App\Models\FacultySummary;
use App\Models\FacultyTrend;
use App\Models\SimilarityRanking;
use App\Models\TrajectorySimilarity;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    // Actual filenames on disk (parentheses, not a subdirectory)
    private const FILES = [
        'faculty_summary'       => 'faculty_exports_20260706_200639(faculty_summary).csv',
        'faculty_trends'        => 'faculty_exports_20260706_200639(faculty_trends).csv',
        'similarity_ranks'      => 'faculty_exports_20260706_200639(similarity_ranks).csv',
        'trajectory_similarity' => 'faculty_exports_20260706_200639(trajectory_similarity).csv',
    ];

    public function index()
    {
        $counts = [
            'faculty_summaries'       => FacultySummary::count(),
            'faculty_trends'          => FacultyTrend::count(),
            'similarity_rankings'     => SimilarityRanking::count(),
            'trajectory_similarities' => TrajectorySimilarity::count(),
        ];

        return view('imports.index', compact('counts'));
    }

    public function importFacultySummary()
    {
        try {
            $count = $this->importRows(
                FacultySummary::class,
                $this->csvPath('faculty_summary')
            );
            return back()->with('status', 'Imported ' . $this->fmt($count) . ' faculty summary rows.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Faculty Summary import failed: ' . $e->getMessage());
        }
    }

    public function importFacultyTrends()
    {
        try {
            $count = $this->importRows(
                FacultyTrend::class,
                $this->csvPath('faculty_trends')
            );
            return back()->with('status', 'Imported ' . $this->fmt($count) . ' faculty trend rows.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Faculty Trends import failed: ' . $e->getMessage());
        }
    }

    public function importSimilarityRankings()
    {
        try {
            $count = $this->importRows(
                SimilarityRanking::class,
                $this->csvPath('similarity_ranks'),
                ['9d_similarity_rank' => 'nine_d_similarity_rank']
            );
            return back()->with('status', 'Imported ' . $this->fmt($count) . ' similarity ranking rows.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Similarity Rankings import failed: ' . $e->getMessage());
        }
    }

    public function importTrajectorySimilarities()
    {
        try {
            $count = $this->importRows(
                TrajectorySimilarity::class,
                $this->csvPath('trajectory_similarity')
            );
            return back()->with('status', 'Imported ' . $this->fmt($count) . ' trajectory similarity rows.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Trajectory Similarity import failed: ' . $e->getMessage());
        }
    }

    public function importAll()
    {
        try {
            $counts = [];
            $counts[] = $this->importRows(FacultySummary::class,     $this->csvPath('faculty_summary'));
            $counts[] = $this->importRows(FacultyTrend::class,        $this->csvPath('faculty_trends'));
            $counts[] = $this->importRows(SimilarityRanking::class,   $this->csvPath('similarity_ranks'),
                            ['9d_similarity_rank' => 'nine_d_similarity_rank']);
            $counts[] = $this->importRows(TrajectorySimilarity::class, $this->csvPath('trajectory_similarity'));

            $total = array_sum($counts);
            return back()->with('status', 'Import All complete — ' . $this->fmt($total) . ' total rows imported.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Import All failed: ' . $e->getMessage());
        }
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function csvPath(string $key): string
    {
        return storage_path('app/private/' . self::FILES[$key]);
    }

    /**
     * Truncate the table and insert all rows from a CSV, in chunks of 500.
     * Returns the number of rows inserted.
     */
    private function importRows(string $modelClass, string $filePath, array $columnMap = []): int
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $modelClass::truncate();

        $rows = $this->readCsv($filePath, $columnMap);
        $now  = now()->toDateTimeString();
        $count = 0;

        foreach (array_chunk($rows, 500) as $chunk) {
            $insert = array_map(function (array $row) use ($now): array {
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
                return $row;
            }, $chunk);

            DB::table((new $modelClass)->getTable())->insert($insert);
            $count += count($chunk);
        }

        return $count;
    }

    /**
     * Parse a CSV file into an array of associative arrays.
     * Applies $columnMap to rename headers before returning.
     */
    private function readCsv(string $path, array $columnMap = []): array
    {
        $handle = fopen($path, 'r');

        $headers = fgetcsv($handle);

        // Strip UTF-8 BOM from the first header if present
        if ($headers && str_starts_with($headers[0], "\xEF\xBB\xBF")) {
            $headers[0] = substr($headers[0], 3);
        }

        // Rename any headers specified in the column map
        $headers = array_map(fn($h) => $columnMap[$h] ?? $h, $headers);

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            // Skip rows that don't align with the header count
            if (count($line) !== count($headers)) {
                continue;
            }
            $row = [];
            foreach ($headers as $i => $col) {
                $row[$col] = $this->normalizeValue($line[$i] ?? '');
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Normalize a single CSV cell value.
     * - Empty string → null
     * - Trailing % → strip and divide by 100
     * - Preserve 0 as 0
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value === '' || $value === null) {
            return null;
        }

        if (is_string($value) && str_ends_with(trim($value), '%')) {
            return (float) rtrim(trim($value), '%') / 100;
        }

        return $value;
    }

    private function fmt(int $n): string
    {
        return number_format($n);
    }
}
