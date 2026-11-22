@extends('errors.layout')

@section('code', '419')
@section('title', 'Sesi halaman berakhir')
@section('message', 'Halaman terlalu lama tidak aktif sehingga token keamanan sudah kedaluwarsa. Muat ulang halaman lalu coba kembali.')

@section('secondary_action')
    <button type="button" onclick="window.location.reload()" class="btn btn-secondary">Muat ulang</button>
@endsection
