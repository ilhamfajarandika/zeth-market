@extends('errors.layout')

@section('code', '503')
@section('title', 'Sedang dalam pemeliharaan')
@section('message', 'Layanan sedang diperbarui sementara waktu. Kembali beberapa menit lagi untuk melanjutkan top up.')

@section('secondary_action')
    <button type="button" onclick="window.location.reload()" class="btn btn-secondary">Muat ulang</button>
@endsection
