<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $fillable = [
        'kode_pelanggan',
        'nama_pelanggan',
        'password_pelanggan',
        'alamat_pelanggan',
        'rt',
        'rw',
        'kelurahan_id',
        'kecamatan',
        'telp_user',
        'id_telegram',
        'status_log',
        'status_followup',
        'stts_send_survei',
        'log_aktivasi',
        'va_bri',
        'va_bca',
        'unit_id',
        'harga_paket_id',
        'no_combo',
        'log_username_dcp',
        'pendaftaran_id',
    ];

    public $timestamps = true;

    // <<< penting untuk dashboard berbasis log_aktivasi
    protected $casts = [
        'log_aktivasi' => 'datetime',
    ];
}
