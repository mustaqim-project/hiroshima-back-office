<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa untuk Validasi
    |--------------------------------------------------------------------------
    */

    'accepted' => 'Kolom :attribute harus diterima.',
    'accepted_if' => 'Kolom :attribute harus diterima ketika :other bernilai :value.',
    'active_url' => 'Kolom :attribute bukan URL yang valid.',
    'after' => 'Kolom :attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => 'Kolom :attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha' => 'Kolom :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Kolom :attribute hanya boleh berisi huruf, angka, setrip, dan garis bawah.',
    'alpha_num' => 'Kolom :attribute hanya boleh berisi huruf dan angka.',
    'array' => 'Kolom :attribute harus berupa sebuah array.',
    'before' => 'Kolom :attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => 'Kolom :attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => 'Kolom :attribute harus memiliki :min sampai :max item.',
        'file' => 'Kolom :attribute harus berukuran antara :min sampai :max kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai antara :min sampai :max.',
        'string' => 'Kolom :attribute harus berisi antara :min sampai :max karakter.',
    ],
    'boolean' => 'Kolom :attribute harus bernilai true atau false.',
    'confirmed' => 'Konfirmasi kolom :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => 'Kolom :attribute bukan tanggal yang valid.',
    'date_equals' => 'Kolom :attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => 'Kolom :attribute tidak cocok dengan format :format.',
    'declined' => 'Kolom :attribute harus ditolak.',
    'declined_if' => 'Kolom :attribute harus ditolak ketika :other bernilai :value.',
    'different' => 'Kolom :attribute dan :other harus berbeda.',
    'digits' => 'Kolom :attribute harus terdiri dari :digits digit.',
    'digits_between' => 'Kolom :attribute harus terdiri dari :min sampai :max digit.',
    'dimensions' => 'Kolom :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Kolom :attribute memiliki nilai duplikat.',
    'email' => 'Kolom :attribute harus berupa alamat surel yang valid.',
    'ends_with' => 'Kolom :attribute harus diakhiri dengan salah satu dari: :values.',
    'enum' => 'Nilai :attribute yang dipilih tidak valid.',
    'exists' => 'Nilai :attribute yang dipilih tidak valid.',
    'file' => 'Kolom :attribute harus berupa berkas.',
    'filled' => 'Kolom :attribute harus memiliki nilai.',
    'gt' => [
        'array' => 'Kolom :attribute harus memiliki lebih dari :value item.',
        'file' => 'Kolom :attribute harus lebih besar dari :value kilobita.',
        'numeric' => 'Kolom :attribute harus lebih besar dari :value.',
        'string' => 'Kolom :attribute harus lebih panjang dari :value karakter.',
    ],
    'gte' => [
        'array' => 'Kolom :attribute harus memiliki :value item atau lebih.',
        'file' => 'Kolom :attribute harus lebih besar dari atau sama dengan :value kilobita.',
        'numeric' => 'Kolom :attribute harus lebih besar dari atau sama dengan :value.',
        'string' => 'Kolom :attribute harus lebih panjang dari atau sama dengan :value karakter.',
    ],
    'image' => 'Kolom :attribute harus berupa gambar.',
    'in' => 'Nilai :attribute yang dipilih tidak valid.',
    'in_array' => 'Kolom :attribute tidak ada di dalam :other.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
    'ip' => 'Kolom :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Kolom :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Kolom :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Kolom :attribute harus berupa format JSON yang valid.',
    'lt' => [
        'array' => 'Kolom :attribute harus memiliki kurang dari :value item.',
        'file' => 'Kolom :attribute harus lebih kecil dari :value kilobita.',
        'numeric' => 'Kolom :attribute harus lebih kecil dari :value.',
        'string' => 'Kolom :attribute harus lebih pendek dari :value karakter.',
    ],
    'lte' => [
        'array' => 'Kolom :attribute tidak boleh memiliki lebih dari :value item.',
        'file' => 'Kolom :attribute harus lebih kecil dari atau sama dengan :value kilobita.',
        'numeric' => 'Kolom :attribute harus lebih kecil dari atau sama dengan :value.',
        'string' => 'Kolom :attribute harus lebih pendek dari atau sama dengan :value karakter.',
    ],
    'max' => [
        'array' => 'Kolom :attribute tidak boleh memiliki lebih dari :max item.',
        'file' => 'Kolom :attribute tidak boleh lebih besar dari :max kilobita.',
        'numeric' => 'Kolom :attribute tidak boleh lebih besar dari :max.',
        'string' => 'Kolom :attribute tidak boleh lebih panjang dari :max karakter.',
    ],
    'mimes' => 'Kolom :attribute harus berupa berkas bertipe: :values.',
    'mimetypes' => 'Kolom :attribute harus berupa berkas bertipe: :values.',
    'min' => [
        'array' => 'Kolom :attribute minimal harus memiliki :min item.',
        'file' => 'Kolom :attribute minimal harus berukuran :min kilobita.',
        'numeric' => 'Kolom :attribute minimal bernilai :min.',
        'string' => 'Kolom :attribute minimal harus berisi :min karakter.',
    ],
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_if' => 'Kolom :attribute wajib diisi ketika :other bernilai :value.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other berada dalam :values.',
    'required_with' => 'Kolom :attribute wajib diisi ketika terdapat :values.',
    'required_with_all' => 'Kolom :attribute wajib diisi ketika terdapat :values.',
    'required_without' => 'Kolom :attribute wajib diisi ketika tidak terdapat :values.',
    'required_without_all' => 'Kolom :attribute wajib diisi ketika tidak ada satupun :values yang ada.',
    'same' => 'Kolom :attribute dan :other harus cocok.',
    'size' => [
        'array' => 'Kolom :attribute harus berisi :size item.',
        'file' => 'Kolom :attribute harus berukuran :size kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai :size.',
        'string' => 'Kolom :attribute harus berisi :size karakter.',
    ],
    'string' => 'Kolom :attribute harus berupa teks string.',
    'timezone' => 'Kolom :attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah terdaftar / sudah digunakan.',
    'uploaded' => 'Berkas :attribute gagal diunggah.',
    'url' => 'Kolom :attribute harus berupa format URL yang valid.',
    'uuid' => 'Kolom :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Kustomisasi Nama Atribut
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'nama',
        'code' => 'kode unik',
        'sort_order' => 'nomor urut',
        'is_default' => 'pilihan default',
        'is_active' => 'status aktif',
        'category_id' => 'kategori',
        'base_price' => 'harga dasar',
        'cogs' => 'HPP (Harga Pokok Penjualan)',
        'prices' => 'daftar harga',
        'amount' => 'nominal uang',
        'type' => 'tipe transaksi',
        'category' => 'kategori transaksi',
        'description' => 'keterangan',
        'transaction_date' => 'tanggal transaksi',
        'photo' => 'bukti foto',
        'branch_id' => 'cabang / outlet',
    ],

];
