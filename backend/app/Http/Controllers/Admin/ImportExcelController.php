<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportExcelRequest;
use App\Services\Admin\ExcelImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportExcelController extends Controller
{
    public function __construct(private readonly ExcelImportService $excelImportService) {}

    public function template(Request $request): BinaryFileResponse
    {
        abort_unless(Auth::user()?->role === 'admin', 403);

        $path = tempnam(sys_get_temp_dir(), 'internekit_import_');
        if ($path === false) {
            abort(500, 'Impossible de créer le modèle Excel.');
        }

        try {
            (new Xlsx($this->excelImportService->createTemplate()))->save($path);

            return response()->download($path, 'internekit_import.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            @unlink($path);
            throw $exception;
        }
    }

    public function store(ImportExcelRequest $request): JsonResponse
    {
        $summary = $this->excelImportService->import($request->file('file')->getRealPath());

        return response()->json(['data' => $summary], 201);
    }
}
