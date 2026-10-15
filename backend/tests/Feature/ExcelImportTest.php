<?php

use App\Models\Interne;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function excelImportAdmin(): User
{
    return User::query()->create([
        'name' => 'Excel Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'role' => 'admin',
    ]);
}

function makeExcelImportFile(array $rowsBySheet): string
{
    $spreadsheet = new Spreadsheet;
    $first = true;

    foreach ($rowsBySheet as $sheetName => $rows) {
        $sheet = $first ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
        $first = false;
        $sheet->setTitle($sheetName);
        $sheet->fromArray($rows, null, 'A1', true);
    }

    $path = tempnam(sys_get_temp_dir(), 'internekit_test_');
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

function completeExcelImportRows(): array
{
    return [
        'Utilisateurs' => [
            ['ref', 'nom', 'email', 'telephone', 'role', 'actif', 'mot_de_passe'],
            ['U1', 'Utilisateur Excel', 'excel-user@example.test', '+228 90 00 00 10', 'user', 'oui', 'SecurePass123'],
        ],
        'Hopitaux' => [
            ['ref', 'nom'],
            ['H1', 'CHU Campus'],
        ],
        'Services' => [
            ['ref', 'hopital_ref', 'nom'],
            ['S1', 'H1', 'Cardiologie'],
        ],
        'Internes' => [
            ['ref', 'nom', 'telephone'],
            ['I1', 'Interne Excel', '+228 90 00 00 01'],
        ],
        'Rotations' => [
            ['ref', 'nom', 'debut', 'fin'],
            ['R1', 'Rotation Excel', '2031-01-01', '2031-01-31'],
        ],
        'TypesGarde' => [
            ['ref', 'nom', 'heure_debut', 'heure_fin', 'fin_jour_suivant'],
            ['T1', 'Garde jour Excel', '08:00', '16:00', 0],
        ],
        'JoursFeries' => [
            ['date', 'nom'],
            ['2031-01-01', 'Jour Excel'],
        ],
        'Affectations' => [
            ['interne_ref', 'service_ref', 'rotation_ref'],
            ['I1', 'S1', 'R1'],
        ],
        'Gardes' => [
            ['date_garde', 'type_garde_ref', 'service_ref', 'internes_refs'],
            ['2031-01-02', 'T1', 'S1', 'I1'],
        ],
    ];
}

it('requires an admin for Excel upload and template download', function () {
    $this->postJson('/api/imports/excel')->assertUnauthorized();
    $this->get('/api/imports/excel/template')->assertUnauthorized();

    $user = excelImportAdmin();
    $this->actingAs($user)->get('/api/imports/excel/template')->assertOk();
});

it('imports all workbook sheets transactionally and reuses existing rows on reimport', function () {
    $admin = excelImportAdmin();
    $path = makeExcelImportFile(completeExcelImportRows());

    try {
        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/api/imports/excel', [
                'file' => new UploadedFile(
                    $path,
                    'internes.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    UPLOAD_ERR_OK,
                    true,
                ),
            ])
            ->assertCreated()
            ->assertJsonPath('data.Utilisateurs.created', 1)
            ->assertJsonPath('data.Hopitaux.created', 1)
            ->assertJsonPath('data.Services.created', 1)
            ->assertJsonPath('data.Affectations.created', 1)
            ->assertJsonPath('data.Gardes.created', 1);

        $this->assertDatabaseCount('hopitaux', 1);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('services', 1);
        $this->assertDatabaseCount('internes', 1);
        $this->assertDatabaseCount('periodes_rotation', 1);
        $this->assertDatabaseCount('types_garde', 1);
        $this->assertDatabaseCount('jours_feries', 1);
        $this->assertDatabaseCount('affectations', 1);
        $this->assertDatabaseCount('gardes', 1);
        $this->assertDatabaseCount('garde_interne', 1);
        $importedUser = User::query()->where('email', 'excel-user@example.test')->firstOrFail();
        expect(Hash::check('SecurePass123', $importedUser->password))->toBeTrue();

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/api/imports/excel', [
                'file' => new UploadedFile(
                    $path,
                    'internes.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    UPLOAD_ERR_OK,
                    true,
                ),
            ])
            ->assertCreated()
            ->assertJsonPath('data.Utilisateurs.reused', 1)
            ->assertJsonPath('data.Hopitaux.reused', 1)
            ->assertJsonPath('data.Services.reused', 1)
            ->assertJsonPath('data.Affectations.reused', 1)
            ->assertJsonPath('data.Gardes.reused', 1);

        $this->assertDatabaseCount('garde_interne', 1);
    } finally {
        @unlink($path);
    }
});

it('rolls back the entire workbook when a sheet references an unknown row', function () {
    $admin = excelImportAdmin();
    $rows = completeExcelImportRows();
    $rows['Services'][1][1] = 'HOSPITAL_MISSING';
    $path = makeExcelImportFile($rows);

    try {
        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/api/imports/excel', [
                'file' => new UploadedFile(
                    $path,
                    'invalid.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    UPLOAD_ERR_OK,
                    true,
                ),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['Services.2.hopital_ref']);

        $this->assertDatabaseCount('hopitaux', 0);
        $this->assertDatabaseCount('services', 0);
        $this->assertDatabaseCount('internes', 0);
        $this->assertDatabaseMissing('users', ['email' => 'excel-user@example.test']);
    } finally {
        @unlink($path);
    }
});

it('reuses the unique existing interne by name when Excel omits the phone', function () {
    $admin = excelImportAdmin();
    $interne = Interne::query()->create([
        'name' => 'Interne Excel',
        'phone' => '+228 90 00 00 09',
    ]);
    $rows = completeExcelImportRows();
    $rows['Internes'][1][2] = null;
    $path = makeExcelImportFile($rows);

    try {
        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/api/imports/excel', [
                'file' => new UploadedFile(
                    $path,
                    'internes.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    UPLOAD_ERR_OK,
                    true,
                ),
            ])
            ->assertCreated();

        $this->assertDatabaseCount('internes', 1);
        $this->assertDatabaseHas('internes', [
            'id' => $interne->id,
            'name' => 'Interne Excel',
            'phone' => '+228 90 00 00 09',
        ]);
        $this->assertDatabaseHas('affectations', ['interne_id' => $interne->id]);
    } finally {
        @unlink($path);
    }
});
