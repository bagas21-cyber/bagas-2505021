<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function sales(Request $request)
    {
        return $this->render('sales', $request);
    }

    public function stock(Request $request)
    {
        return $this->render('stock', $request);
    }

    public function profit(Request $request)
    {
        return $this->render('profit', $request);
    }

    private function render(string $type, Request $request)
    {
        [$from, $to] = $this->reports->dateRange($request->query('from'), $request->query('to'));
        $data = $this->reports->{$type}($from, $to);

        return view('reports.index', array_merge($data, compact('type', 'from', 'to')));
    }

    public function export(Request $request): StreamedResponse|BinaryFileResponse
    {
        [$from, $to] = $this->reports->dateRange($request->query('from'), $request->query('to'));
        $type = $this->type($request);
        $format = strtolower((string) $request->query('format', 'csv'));
        $data = $this->reports->{$type}($from, $to);
        $filename = 'laporan-'.$type.'-'.$from->format('Ymd').'-'.$to->format('Ymd');

        if ($format === 'pdf' && class_exists(Pdf::class)) {
            return Pdf::loadView('reports.pdf', array_merge($data, compact('type', 'from', 'to')))
                ->download($filename.'.pdf');
        }

        if ($format === 'xlsx' && class_exists(Excel::class)) {
            return Excel::download(new ReportExport($type, $data, $from, $to), $filename.'.xlsx');
        }

        return response()->streamDownload(function () use ($type, $data): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ReportExport::headings($type));
            foreach (ReportExport::rows($type, $data) as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function type(Request $request): string
    {
        return in_array($request->query('type'), ['sales', 'stock', 'profit'], true)
            ? (string) $request->query('type')
            : 'sales';
    }
}
