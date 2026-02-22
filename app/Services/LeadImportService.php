<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class LeadImportService
{
    public function __construct(
        private readonly LeadScoringService $leadScoringService,
        private readonly LeadActivityService $leadActivityService,
        private readonly OpportunityService $opportunityService,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function import(UploadedFile $file, User $actor): array
    {
        $rows = $this->readCsvRows($file);

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'file' => 'CSV is empty or missing header row.',
            ]);
        }

        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), $rows[0]);
        $required = ['name', 'email', 'phone', 'source', 'status', 'assigned_email', 'notes', 'follow_up_date'];

        $missingHeaders = array_values(array_diff($required, $headers));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'file' => 'Missing required columns: '.implode(', ', $missingHeaders),
            ]);
        }

        $headerMap = array_flip($headers);
        $errors = [];
        $payloadRows = [];
        $emailsSeen = [];
        $phonesSeen = [];

        $validStatuses = Lead::STATUSES;

        foreach (array_slice($rows, 1) as $index => $row) {
            $line = $index + 2;
            $data = [
                'name' => trim((string) ($row[$headerMap['name']] ?? '')),
                'email' => trim((string) ($row[$headerMap['email']] ?? '')),
                'phone' => trim((string) ($row[$headerMap['phone']] ?? '')),
                'source' => trim((string) ($row[$headerMap['source']] ?? '')),
                'status' => strtolower(trim((string) ($row[$headerMap['status']] ?? 'new'))),
                'assigned_email' => strtolower(trim((string) ($row[$headerMap['assigned_email']] ?? ''))),
                'notes' => trim((string) ($row[$headerMap['notes']] ?? '')),
                'follow_up_date' => trim((string) ($row[$headerMap['follow_up_date']] ?? '')),
            ];

            if ($data['name'] === '') {
                $errors[] = "Row {$line}: name is required.";
            }

            if ($data['email'] !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$line}: invalid email format.";
            }

            if ($data['status'] === '' || ! in_array($data['status'], $validStatuses, true)) {
                $errors[] = "Row {$line}: status must be one of ".implode(', ', $validStatuses).'.';
            }

            if ($data['follow_up_date'] !== '' && ! strtotime($data['follow_up_date'])) {
                $errors[] = "Row {$line}: follow_up_date must be a valid date.";
            }

            if ($data['assigned_email'] !== '') {
                $assigned = User::query()->where('email', $data['assigned_email'])->first();
                if (! $assigned) {
                    $errors[] = "Row {$line}: assigned_email user not found.";
                } elseif ($assigned->role !== 'sales_executive' && ! $actor->isAdmin()) {
                    $errors[] = "Row {$line}: only admins can assign to other users.";
                }
            } else {
                $assigned = $actor->isSalesExecutive() ? $actor : null;
            }

            if ($data['email'] !== '') {
                $emailKey = strtolower($data['email']);
                if (isset($emailsSeen[$emailKey])) {
                    $errors[] = "Row {$line}: duplicate email inside file ({$data['email']}).";
                }
                $emailsSeen[$emailKey] = true;

                $existsEmail = Lead::query()->whereRaw('LOWER(email) = ?', [$emailKey])->exists();
                if ($existsEmail) {
                    $errors[] = "Row {$line}: duplicate email already exists ({$data['email']}).";
                }
            }

            if ($data['phone'] !== '') {
                $phoneKey = preg_replace('/\s+/', '', $data['phone']);
                if (isset($phonesSeen[$phoneKey])) {
                    $errors[] = "Row {$line}: duplicate phone inside file ({$data['phone']}).";
                }
                $phonesSeen[$phoneKey] = true;

                $existsPhone = Lead::query()->where('phone', $data['phone'])->exists();
                if ($existsPhone) {
                    $errors[] = "Row {$line}: duplicate phone already exists ({$data['phone']}).";
                }
            }

            $payloadRows[] = [
                'line' => $line,
                'data' => $data,
                'assigned_user_id' => $assigned?->id,
            ];
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => $errors,
            ];
        }

        DB::transaction(function () use ($payloadRows, $actor): void {
            foreach ($payloadRows as $row) {
                $lead = Lead::query()->create([
                    'name' => $row['data']['name'],
                    'email' => $row['data']['email'] ?: null,
                    'phone' => $row['data']['phone'] ?: null,
                    'source' => $row['data']['source'] ?: null,
                    'status' => $row['data']['status'] ?: 'new',
                    'assigned_user_id' => $row['assigned_user_id'],
                    'notes' => $row['data']['notes'] ?: null,
                    'follow_up_date' => $row['data']['follow_up_date'] ?: null,
                ]);

                $lead->update($this->leadScoringService->calculate($lead));

                $this->leadActivityService->log(
                    $lead,
                    $actor,
                    'lead_imported',
                    'Lead imported from CSV file.',
                    ['line' => $row['line']]
                );

                if ($lead->assigned_user_id) {
                    $this->notificationService->notifyLeadAssigned($lead, $lead->assignedUser, $actor);
                }

                if ($lead->status === 'converted') {
                    $opportunity = $this->opportunityService->createFromLead($lead, $actor);
                    $this->notificationService->notifyLeadConverted($lead, $actor);
                    $this->notificationService->notifyOpportunityCreated($opportunity, $actor);
                }
            }
        });

        return [
            'success' => true,
            'imported' => count($payloadRows),
            'errors' => [],
        ];
    }

    private function readCsvRows(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if ($extension === 'xlsx') {
            return $this->readXlsxRows($file);
        }

        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return $rows;
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(UploadedFile $file): array
    {
        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages([
                'file' => 'Unable to read Excel file.',
            ]);
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml);
            if ($xml !== false) {
                foreach ($xml->si as $item) {
                    $sharedStrings[] = (string) $item->t;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw ValidationException::withMessages([
                'file' => 'Excel worksheet is missing.',
            ]);
        }

        $xml = simplexml_load_string($sheetXml);
        if ($xml === false || ! isset($xml->sheetData)) {
            throw ValidationException::withMessages([
                'file' => 'Invalid Excel structure.',
            ]);
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $line = [];
            foreach ($row->c as $cell) {
                $type = (string) $cell['t'];
                $value = (string) ($cell->v ?? '');
                if ($type === 's') {
                    $sharedIndex = (int) $value;
                    $line[] = $sharedStrings[$sharedIndex] ?? '';
                } else {
                    $line[] = $value;
                }
            }
            $rows[] = $line;
        }

        return $rows;
    }
}
