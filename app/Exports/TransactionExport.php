<?php

namespace App\Exports;

use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Transaction::with([
            'course:id,title',
            'student:id,username,email,info'
        ])->get(['id', 'user_id', 'status', 'price', 'course_id', 'created_at']);
    }

    public function map($transaction): array
    {
        return [
            $transaction->id,
            Carbon::parse($transaction->created_at)->format('d F Y'),
            $transaction->student->info['fullname'],
            $transaction->course->title,
            $transaction->price,
            $transaction->status == 'success' ? 'Sukses' : ($transaction->status == 'fail' ? 'Gagal' : 'Pending')
        ];
    }

    public function headings(): array
    {
        return [
            'ID Transaksi',
            'Waktu Pembelian',
            'Nama Karyawan',
            'Nama Materi',
            'Harga',
            'Status'
        ];
    }
}
