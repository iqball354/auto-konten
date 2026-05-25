<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class paymensettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentSetting::set('qris_code', null, 'Kode/URL QRIS');
        PaymentSetting::set('qris_name', 'Nama Bisnis', 'Nama di QRIS');
        PaymentSetting::set('qris_nominal', '100000', 'Nominal Pembayaran');
        PaymentSetting::set('qris_catatan', 'Harap transfer sesuai nominal dan upload bukti pembayaran.', 'Catatan Pembayaran');
    }
}
