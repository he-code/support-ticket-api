<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class UserImportService
{
    public function import(UploadedFile $file, int $createdById, bool $updateExisting = false, ?string $defaultPassword = null): UserImport
    {
        $rows = $this->rowsFromFile($file);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $row = $this->normalizeRow($row);

            $validator = Validator::make($row, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'role' => 'nullable|string|in:user,support_agent,admin',
                'password' => 'nullable|string|min:6',
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'email' => $row['email'] ?? null,
                    'errors' => $validator->errors()->all(),
                ];

                continue;
            }

            $existingUser = User::where('email', $row['email'])->first();

            if ($existingUser && ! $updateExisting) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'email' => $row['email'],
                    'errors' => ['User already exists'],
                ];

                continue;
            }

            $attributes = [
                'name' => $row['name'],
                'email' => $row['email'],
                'role' => $row['role'] ?? 'user',
            ];

            if (! $existingUser || ! empty($row['password']) || $defaultPassword) {
                $attributes['password'] = Hash::make($row['password'] ?: ($defaultPassword ?: 'password'));
            }

            if ($existingUser) {
                $existingUser->update($attributes);
                $updated++;
            } else {
                User::create($attributes);
                $created++;
            }
        }

        return UserImport::create([
            'created_by_id' => $createdById,
            'original_name' => $file->getClientOriginalName(),
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'errors' => $errors,
        ]);
    }

    private function rowsFromFile(UploadedFile $file): array
    {
        return match (strtolower($file->getClientOriginalExtension())) {
            'xlsx' => $this->rowsFromXlsx($file->getRealPath()),
            default => $this->rowsFromCsv($file->getRealPath()),
        };
    }

    private function rowsFromCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            throw new RuntimeException('Unable to read import file');
        }

        $headers = null;
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn ($header) => Str::snake(trim((string) $header)), $data);

                continue;
            }

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $rows[] = array_combine($headers, array_pad($data, count($headers), null));
        }

        fclose($handle);

        return $rows;
    }

    private function rowsFromXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('XLSX import requires the PHP zip extension');
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open XLSX file');
        }

        $sharedStrings = $this->sharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $sheetXml) {
            throw new RuntimeException('XLSX file does not contain sheet1');
        }

        $sheet = simplexml_load_string($sheetXml);

        if (! $sheet instanceof SimpleXMLElement) {
            throw new RuntimeException('Unable to parse XLSX sheet');
        }

        $rawRows = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];

            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                $columnIndex = $this->columnIndex(preg_replace('/\d+/', '', $reference));
                $value = (string) $cell->v;

                if ((string) $cell['t'] === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                }

                $cells[$columnIndex] = $value;
            }

            if ($cells !== []) {
                ksort($cells);
                $rawRows[] = $cells;
            }
        }

        if ($rawRows === []) {
            return [];
        }

        $headers = array_map(
            fn ($header) => Str::snake(trim((string) $header)),
            array_values($rawRows[0])
        );

        return collect(array_slice($rawRows, 1))
            ->filter(fn ($row) => ! $this->isEmptyRow($row))
            ->map(fn ($row) => array_combine($headers, array_pad(array_values($row), count($headers), null)))
            ->values()
            ->all();
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if (! $xml) {
            return [];
        }

        $strings = simplexml_load_string($xml);

        if (! $strings instanceof SimpleXMLElement) {
            return [];
        }

        $values = [];

        foreach ($strings->si as $item) {
            $values[] = (string) ($item->t ?? $item->r->t ?? '');
        }

        return $values;
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord(strtoupper($letter)) - 64);
        }

        return $index - 1;
    }

    private function normalizeRow(array $row): array
    {
        $aliases = [
            'nombre' => 'name',
            'correo' => 'email',
            'email_address' => 'email',
            'rol' => 'role',
            'contrasena' => 'password',
            'contraseña' => 'password',
        ];

        foreach ($aliases as $from => $to) {
            if (array_key_exists($from, $row) && ! array_key_exists($to, $row)) {
                $row[$to] = $row[$from];
            }
        }

        return collect($row)
            ->mapWithKeys(fn ($value, $key) => [Str::snake((string) $key) => is_string($value) ? trim($value) : $value])
            ->toArray();
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->isEmpty();
    }
}
