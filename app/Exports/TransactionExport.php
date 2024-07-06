<?php

namespace App\Exports;

use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;

class TransactionExport implements FromCollection
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
            Carbon::parse($transaction->created_at)->format('d F Y'), // Format tanggal
            $transaction->student->info['fullname'], // Nama karyawan
            $transaction->course->title, // Nama materi
            $transaction->price,
            $transaction->status == 'success' ? 'Sukses' : ($transaction->status == 'fail' ? 'Gagal' : 'Pending') // Status
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
