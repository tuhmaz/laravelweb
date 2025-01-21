<?php

namespace App\Exports;

use App\Models\SecurityLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class SecurityLogsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query ? $this->query->get() : SecurityLog::all();
    }

    public function headings(): array
    {
        return [
            'رقم السجل',
            'نوع الحدث',
            'الوصف',
            'عنوان IP',
            'المستخدم',
            'الحالة',
            'تاريخ الإنشاء',
        ];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->event_type,
            $log->description,
            $log->ip_address,
            $log->user ? $log->user->name : 'N/A',
            $log->status,
            Carbon::parse($log->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
