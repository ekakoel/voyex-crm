<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerImportController extends Controller
{
    private const SESSION_KEY = 'customer_import_preview';

    public function create()
    {
        return view('modules.customers.import');
    }

    public function template(): StreamedResponse
    {
        $headers = ['code', 'name', 'email', 'phone', 'address', 'country', 'customer_type', 'company_name'];

        $rows = [
            ['CUST-IMPORT-001', 'John Doe', 'john@example.com', '08123456789', 'Jakarta', 'Indonesia', 'individual', ''],
            ['CUST-IMPORT-002', 'Acme Corp', 'sales@acme.com', '021123456', 'Bandung', 'Indonesia', 'company', 'Acme Corp'],
        ];

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'customer-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'mode' => ['required', 'in:skip,update'],
        ]);

        $handle = fopen($validated['file']->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', 'Failed to read CSV file.');
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            return back()->with('error', 'CSV is empty or has an invalid format.');
        }

        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);
        if (! in_array('name', $headers, true)) {
            fclose($handle);
            return back()->with('error', 'Required column not found: name');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 0) {
                continue;
            }
            $data = array_combine($headers, $row);
            if (! $data) {
                continue;
            }

            $normalized = $this->normalizeRow($data);
            $duplicate = $this->findDuplicate($normalized['email'], $normalized['phone'], $normalized['code']);

            $rows[] = [
                'data' => $normalized,
                'duplicate' => $duplicate ? true : false,
                'duplicate_id' => $duplicate?->id,
                'action' => $duplicate ? ($validated['mode'] === 'update' ? 'update' : 'skip') : 'create',
            ];
        }
        fclose($handle);

        Session::put(self::SESSION_KEY, [
            'mode' => $validated['mode'],
            'rows' => $rows,
        ]);

        return view('modules.customers.import-preview', [
            'mode' => $validated['mode'],
            'rows' => $rows,
        ]);
    }

    public function store(Request $request)
    {
        $payload = Session::get(self::SESSION_KEY);
        if (! $payload) {
            return redirect()->route('customers.import')->with('error', 'Preview not found. Please upload the file again.');
        }

        $rows = $payload['rows'] ?? [];
        $mode = $payload['mode'] ?? 'skip';

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $data = $row['data'];
                if ($row['duplicate']) {
                    if ($mode === 'update') {
                        Customer::query()->where('id', $row['duplicate_id'])->update($data);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    Customer::query()->create($data);
                    $created++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('customers.import')->with('error', 'Import failed. Please check the CSV data.');
        } finally {
            Session::forget(self::SESSION_KEY);
        }

        return redirect()
            ->route('customers.index')
            ->with('success', "Import selesai. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.");
    }

    private function normalizeRow(array $data): array
    {
        return [
            'code' => $this->normalizeCode($data['code'] ?? null),
            'name' => trim((string) ($data['name'] ?? '')),
            'email' => $this->nullableTrim($data['email'] ?? null),
            'phone' => $this->nullableTrim($data['phone'] ?? null),
            'address' => $this->nullableTrim($data['address'] ?? null),
            'country' => $this->nullableTrim($data['country'] ?? null) ?? 'Indonesia',
            'customer_type' => $this->normalizeType($data['customer_type'] ?? 'individual'),
            'company_name' => $this->nullableTrim($data['company_name'] ?? null),
            'created_by' => auth()->id(),
        ];
    }

    private function findDuplicate(?string $email, ?string $phone, ?string $code = null): ?Customer
    {
        if (! $email && ! $phone && ! $code) {
            return null;
        }

        return Customer::query()
            ->when($code, fn ($q) => $q->orWhere('code', $code))
            ->when($email, fn ($q) => $q->orWhere('email', $email))
            ->when($phone, fn ($q) => $q->orWhere('phone', $phone))
            ->first();
    }

    private function normalizeCode(?string $value): string
    {
        $code = strtoupper(trim((string) $value));
        if ($code !== '') {
            return $code;
        }

        do {
            $code = 'CUST-IMP-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (Customer::query()->where('code', $code)->exists());

        return $code;
    }

    private function normalizeType($value): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, ['individual', 'company'], true) ? $value : 'individual';
    }

    private function nullableTrim($value): ?string
    {
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
}



