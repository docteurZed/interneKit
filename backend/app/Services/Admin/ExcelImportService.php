<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Affectation;
use App\Models\Garde;
use App\Models\Hopital;
use App\Models\Interne;
use App\Models\JourFerie;
use App\Models\PeriodeRotation;
use App\Models\Service;
use App\Models\TypeGarde;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelImportService
{
    private const MAX_ROWS_PER_SHEET = 5000;

    private const SHEETS = [
        'Utilisateurs' => ['ref', 'nom', 'email', 'telephone', 'role', 'actif', 'mot_de_passe'],
        'Hopitaux' => ['ref', 'nom'],
        'Services' => ['ref', 'hopital_ref', 'nom'],
        'Internes' => ['ref', 'nom', 'telephone'],
        'Rotations' => ['ref', 'nom', 'debut', 'fin'],
        'TypesGarde' => ['ref', 'nom', 'heure_debut', 'heure_fin', 'fin_jour_suivant'],
        'JoursFeries' => ['date', 'nom'],
        'Affectations' => ['interne_ref', 'service_ref', 'rotation_ref'],
        'Gardes' => ['date_garde', 'type_garde_ref', 'service_ref', 'internes_refs'],
    ];

    public function __construct(
        private readonly PeriodeRotationService $periodeRotationService,
        private readonly AffectationService $affectationService,
        private readonly GardeService $gardeService,
    ) {}

    public function createTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        foreach (self::SHEETS as $index => $headers) {
            $sheet = $index === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();
            $sheet->setTitle($index);
            $sheet->fromArray([$headers], null, 'A1');
            $sheet->freezePane('A2');

            foreach (['debut', 'fin', 'date', 'date_garde'] as $dateColumn) {
                $columnIndex = array_search($dateColumn, $headers, true);
                if ($columnIndex !== false) {
                    $column = Coordinate::stringFromColumnIndex($columnIndex + 1);
                    $sheet->getStyle("{$column}2:{$column}5001")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                }
            }

            foreach (['heure_debut', 'heure_fin'] as $timeColumn) {
                $columnIndex = array_search($timeColumn, $headers, true);
                if ($columnIndex !== false) {
                    $column = Coordinate::stringFromColumnIndex($columnIndex + 1);
                    $sheet->getStyle("{$column}2:{$column}5001")->getNumberFormat()->setFormatCode('hh:mm');
                }
            }

            $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->setAutoFilter("A1:{$lastColumn}1");
            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '24566A']],
            ]);

            foreach (range(1, count($headers)) as $column) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
            }
        }

        return $spreadsheet;
    }

    public function import(string $path): array
    {
        $workbook = IOFactory::load($path);

        try {
            $unknownSheets = array_diff(
                array_map(fn (Worksheet $sheet) => $sheet->getTitle(), $workbook->getAllSheets()),
                array_keys(self::SHEETS),
            );

            if ($unknownSheets !== []) {
                throw ValidationException::withMessages([
                    'file' => ['Feuilles Excel non reconnues: '.implode(', ', $unknownSheets).'.'],
                ]);
            }

            $rows = [];
            foreach (self::SHEETS as $sheetName => $headers) {
                $rows[$sheetName] = $this->readRows($workbook->getSheetByName($sheetName), $sheetName, $headers);
            }

            if (collect($rows)->flatten(1)->isEmpty()) {
                throw ValidationException::withMessages([
                    'file' => ['Le classeur ne contient aucune ligne à importer.'],
                ]);
            }

            return DB::transaction(fn () => $this->importRows($rows));
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    private function importRows(array $rows): array
    {
        $references = [];
        $summary = array_fill_keys(array_keys(self::SHEETS), ['created' => 0, 'reused' => 0]);

        foreach ($rows['Utilisateurs'] as $row) {
            $name = $this->requiredText($row, 'nom', 'Utilisateurs');
            $email = $this->requiredText($row, 'email', 'Utilisateurs');
            $phone = $this->optionalText($row, 'telephone', 'Utilisateurs');
            $role = $this->requiredText($row, 'role', 'Utilisateurs');
            $isActive = $this->booleanValue($row, 'actif', 'Utilisateurs');
            $password = $this->requiredText($row, 'mot_de_passe', 'Utilisateurs');

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->fail($row, 'Utilisateurs', 'email', 'Adresse e-mail invalide.');
            }
            if (! in_array($role, ['admin', 'user'], true)) {
                $this->fail($row, 'Utilisateurs', 'role', 'Le rôle doit être admin ou user.');
            }
            if (mb_strlen($password) < 8) {
                $this->fail($row, 'Utilisateurs', 'mot_de_passe', 'Le mot de passe initial doit contenir au moins 8 caractères.');
            }

            $existing = User::query()->where('email', $email)->first();
            if ($existing && (
                $existing->name !== $name
                || $existing->role !== $role
                || (bool) $existing->is_active !== $isActive
                || ($phone !== null && $existing->phone !== $phone)
            )) {
                $this->fail($row, 'Utilisateurs', 'email', 'Cet e-mail existe déjà avec des données différentes; aucun compte n’a été modifié.');
            }

            $user = $existing ?? User::query()->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'role' => $role,
                'is_active' => $isActive,
                'password' => $password,
            ]);
            $this->countResult($summary, 'Utilisateurs', $existing === null);
            $this->registerReference($references, 'Utilisateurs', $row, $user);
        }

        foreach ($rows['Hopitaux'] as $row) {
            $name = $this->requiredText($row, 'nom', 'Hopitaux');
            $hopital = Hopital::query()->firstOrCreate(['name' => $name]);
            $this->countResult($summary, 'Hopitaux', $hopital->wasRecentlyCreated);
            $this->registerReference($references, 'Hopitaux', $row, $hopital);
        }

        foreach ($rows['Services'] as $row) {
            $name = $this->requiredText($row, 'nom', 'Services');
            $hopital = $this->resolveReference($references, 'Hopitaux', $row, 'hopital_ref', 'Services');
            $service = Service::query()->firstOrCreate([
                'hopital_id' => $hopital->id,
                'name' => $name,
            ]);
            $this->countResult($summary, 'Services', $service->wasRecentlyCreated);
            $this->registerReference($references, 'Services', $row, $service);
        }

        foreach ($rows['Internes'] as $row) {
            $name = $this->requiredText($row, 'nom', 'Internes');
            $phone = $this->optionalText($row, 'telephone', 'Internes');
            $query = Interne::query()->where('name', $name);
            if ($phone !== null) {
                $query->where('phone', $phone);
            }
            $matches = $query->limit(2)->get();

            if ($matches->count() > 1) {
                $this->fail($row, 'Internes', 'telephone', 'Plusieurs internes portent ce nom; renseignez leur téléphone pour les distinguer.');
            }

            $interne = $matches->first() ?? Interne::query()->create(['name' => $name, 'phone' => $phone]);
            $this->countResult($summary, 'Internes', $matches->isEmpty());
            $this->registerReference($references, 'Internes', $row, $interne);
        }

        foreach ($rows['Rotations'] as $row) {
            $name = $this->requiredText($row, 'nom', 'Rotations');
            $startDate = $this->dateValue($row, 'debut', 'Rotations');
            $endDate = $this->dateValue($row, 'fin', 'Rotations');
            $periode = PeriodeRotation::query()
                ->where('name', $name)
                ->whereDate('start_date', $startDate)
                ->whereDate('end_date', $endDate)
                ->first();

            if ($periode) {
                $created = false;
            } else {
                try {
                    $periode = $this->periodeRotationService->create([
                        'name' => $name,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ]);
                    $created = true;
                } catch (BusinessException $exception) {
                    $this->fail($row, 'Rotations', 'debut', $exception->getMessage());
                }
            }

            $this->countResult($summary, 'Rotations', $created);
            $this->registerReference($references, 'Rotations', $row, $periode);
        }

        foreach ($rows['TypesGarde'] as $row) {
            $name = $this->requiredText($row, 'nom', 'TypesGarde');
            $startTime = $this->timeValue($row, 'heure_debut', 'TypesGarde');
            $endTime = $this->timeValue($row, 'heure_fin', 'TypesGarde');
            $endOffset = $this->offsetValue($row, 'fin_jour_suivant');
            $type = TypeGarde::query()->where('name', $name)->first();

            if ($type && (
                substr((string) $type->start_time, 0, 5) !== $startTime
                || substr((string) $type->end_time, 0, 5) !== $endTime
                || (int) $type->decalage_end !== $endOffset
            )) {
                $this->fail($row, 'TypesGarde', 'nom', 'Ce type existe déjà avec des horaires différents; aucune donnée n’a été modifiée.');
            }

            if ($type) {
                $created = false;
            } else {
                $type = TypeGarde::query()->create([
                    'name' => $name,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'decalage_end' => $endOffset,
                ]);
                $created = true;
            }

            $this->countResult($summary, 'TypesGarde', $created);
            $this->registerReference($references, 'TypesGarde', $row, $type);
        }

        foreach ($rows['JoursFeries'] as $row) {
            $date = $this->dateValue($row, 'date', 'JoursFeries');
            $name = $this->requiredText($row, 'nom', 'JoursFeries');
            $jourFerie = JourFerie::query()->whereDate('date', $date)->first();

            if ($jourFerie && $jourFerie->name !== $name) {
                $this->fail($row, 'JoursFeries', 'nom', 'Cette date existe déjà avec un autre nom.');
            }

            if ($jourFerie) {
                $created = false;
            } else {
                $jourFerie = JourFerie::query()->create(['date' => $date, 'name' => $name]);
                $created = true;
            }

            $this->countResult($summary, 'JoursFeries', $created);
        }

        foreach ($rows['Affectations'] as $row) {
            $interne = $this->resolveReference($references, 'Internes', $row, 'interne_ref', 'Affectations');
            $service = $this->resolveReference($references, 'Services', $row, 'service_ref', 'Affectations');
            $periode = $this->resolveReference($references, 'Rotations', $row, 'rotation_ref', 'Affectations');
            $existing = Affectation::query()
                ->where('interne_id', $interne->id)
                ->where('periode_rotation_id', $periode->id)
                ->first();

            if ($existing && $existing->service_id !== $service->id) {
                $this->fail($row, 'Affectations', 'service_ref', 'Cet interne est déjà affecté à un autre service pendant cette rotation.');
            }

            $affectation = $existing ?? $this->affectationService->create([
                'interne_id' => $interne->id,
                'service_id' => $service->id,
                'periode_rotation_id' => $periode->id,
            ]);
            $this->countResult($summary, 'Affectations', $existing === null);
        }

        foreach ($rows['Gardes'] as $row) {
            $date = $this->dateValue($row, 'date_garde', 'Gardes');
            $type = $this->resolveReference($references, 'TypesGarde', $row, 'type_garde_ref', 'Gardes');
            $service = $this->resolveReference($references, 'Services', $row, 'service_ref', 'Gardes');
            $participantRefs = array_values(array_unique(array_filter(array_map(
                fn (string $reference) => trim($reference),
                explode(';', $this->requiredText($row, 'internes_refs', 'Gardes')),
            ))));

            if ($participantRefs === []) {
                $this->fail($row, 'Gardes', 'internes_refs', 'Une garde doit avoir au moins un interne participant.');
            }

            $participants = array_map(fn (string $reference) => $this->resolveReferenceValue(
                $references,
                'Internes',
                $reference,
                $row,
                'internes_refs',
                'Gardes',
            ), $participantRefs);
            $participantIds = array_map(fn (Interne $interne) => $interne->id, $participants);

            $existing = Garde::query()
                ->where('type_garde_id', $type->id)
                ->where('service_id', $service->id)
                ->whereDate('date_garde', $date)
                ->first();

            if ($existing) {
                try {
                    $this->gardeService->addParticipants($existing, $participantIds);
                } catch (BusinessException $exception) {
                    $this->fail($row, 'Gardes', 'internes_refs', $exception->getMessage());
                }
                $created = false;
            } else {
                try {
                    $this->gardeService->create([
                        'date_garde' => $date,
                        'type_garde_id' => $type->id,
                        'service_id' => $service->id,
                        'interne_ids' => $participantIds,
                    ]);
                } catch (BusinessException $exception) {
                    $this->fail($row, 'Gardes', 'internes_refs', $exception->getMessage());
                }
                $created = true;
            }

            $this->countResult($summary, 'Gardes', $created);
        }

        return $summary;
    }

    private function readRows(?Worksheet $sheet, string $sheetName, array $expectedHeaders): array
    {
        if (! $sheet) {
            return [];
        }

        if ($sheet->getHighestDataRow() > self::MAX_ROWS_PER_SHEET + 1) {
            $this->fail(['__row' => 1], $sheetName, 'file', 'La feuille dépasse la limite de '.self::MAX_ROWS_PER_SHEET.' lignes.');
        }

        $matrix = $sheet->toArray(null, false, false, false);
        $headers = array_map(fn ($header) => trim((string) $header), $matrix[0] ?? []);

        if ($headers !== $expectedHeaders) {
            $this->fail(['__row' => 1], $sheetName, 'headers', 'En-têtes attendus: '.implode(', ', $expectedHeaders).'.');
        }

        $rows = [];
        foreach (array_slice($matrix, 1, null, true) as $index => $cells) {
            $values = array_slice(array_pad($cells, count($expectedHeaders), null), 0, count($expectedHeaders));
            if (collect($values)->every(fn ($value) => $value === null || trim((string) $value) === '')) {
                continue;
            }

            $row = array_combine($expectedHeaders, $values);
            $row['__row'] = $index + 1;
            $rows[] = $row;
        }

        return $rows;
    }

    private function requiredText(array $row, string $column, string $sheet): string
    {
        $value = $this->optionalText($row, $column, $sheet);
        if ($value === null) {
            $this->fail($row, $sheet, $column, 'Cette valeur est obligatoire.');
        }

        return $value;
    }

    private function optionalText(array $row, string $column, string $sheet): ?string
    {
        $value = $row[$column] ?? null;
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $text = trim((string) $value);
        if (str_starts_with($text, '=')) {
            $this->fail($row, $sheet, $column, 'Les formules Excel ne sont pas acceptées.');
        }

        return $text;
    }

    private function dateValue(array $row, string $column, string $sheet): string
    {
        $value = $row[$column] ?? null;
        if ($value === null || $value === '') {
            $this->fail($row, $sheet, $column, 'Une date au format AAAA-MM-JJ est obligatoire.');
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value, new DateTimeZone(config('app.timezone')))->format('Y-m-d');
            }

            return CarbonImmutable::parse((string) $value, config('app.timezone'))->toDateString();
        } catch (\Throwable) {
            $this->fail($row, $sheet, $column, 'Date invalide; utilisez le format AAAA-MM-JJ.');
        }
    }

    private function timeValue(array $row, string $column, string $sheet): string
    {
        $value = $row[$column] ?? null;
        if ($value === null || $value === '') {
            $this->fail($row, $sheet, $column, 'Une heure au format HH:MM est obligatoire.');
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value, new DateTimeZone(config('app.timezone')))->format('H:i');
            }

            $time = str_replace('h', ':', strtolower(trim((string) $value)));
            if (! preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
                $this->fail($row, $sheet, $column, 'Heure invalide; utilisez le format HH:MM.');
            }

            return CarbonImmutable::parse($time)->format('H:i');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            $this->fail($row, $sheet, $column, 'Heure invalide; utilisez le format HH:MM.');
        }
    }

    private function offsetValue(array $row, string $column): int
    {
        $value = $row[$column] ?? null;
        if (! is_numeric($value) || ! in_array((int) $value, [0, 1], true) || (float) $value !== (float) (int) $value) {
            $this->fail($row, 'TypesGarde', $column, 'La valeur doit être 0 (même jour) ou 1 (lendemain).');
        }

        return (int) $value;
    }

    private function booleanValue(array $row, string $column, string $sheet): bool
    {
        $value = $row[$column] ?? null;
        if (is_bool($value)) {
            return $value;
        }

        return match (mb_strtolower(trim((string) $value))) {
            '1', 'true', 'oui', 'yes', 'actif' => true,
            '0', 'false', 'non', 'no', 'inactif' => false,
            default => $this->fail($row, $sheet, $column, 'Valeur invalide; utilisez 1/0, vrai/faux, oui/non ou actif/inactif.'),
        };
    }

    private function resolveReference(array $references, string $sheet, array $row, string $column, string $sourceSheet): mixed
    {
        $reference = $this->requiredText($row, $column, $sourceSheet);

        return $this->resolveReferenceValue($references, $sheet, $reference, $row, $column, $sourceSheet);
    }

    private function resolveReferenceValue(array $references, string $sheet, string $reference, array $row, string $column, string $sourceSheet): mixed
    {
        $key = mb_strtolower(trim($reference));
        if (! isset($references[$sheet][$key])) {
            $this->fail($row, $sourceSheet, $column, "Référence '{$reference}' introuvable dans la feuille {$sheet}.");
        }

        return $references[$sheet][$key];
    }

    private function registerReference(array &$references, string $sheet, array $row, object $model): void
    {
        $reference = $this->requiredText($row, 'ref', $sheet);
        $key = mb_strtolower($reference);

        if (isset($references[$sheet][$key])) {
            $this->fail($row, $sheet, 'ref', "La référence '{$reference}' est utilisée plusieurs fois.");
        }

        $references[$sheet][$key] = $model;
    }

    private function countResult(array &$summary, string $sheet, bool $created): void
    {
        $summary[$sheet][$created ? 'created' : 'reused']++;
    }

    private function fail(array $row, string $sheet, string $column, string $message): never
    {
        $line = (int) ($row['__row'] ?? 1);
        throw ValidationException::withMessages([
            "{$sheet}.{$line}.{$column}" => ["{$sheet}, ligne {$line} ({$column}): {$message}"],
        ]);
    }
}
