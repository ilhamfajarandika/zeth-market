@extends('errors.layout')

@section('code', '500')
@section('title', 'Terjadi gangguan di server')
@section('message', 'Sistem mengalami kendala sementara saat memproses permintaanmu. Coba muat ulang beberapa saat lagi atau hubungi admin jika kamu sudah membayar.')

@section('secondary_action')
    <button type="button" onclick="window.location.reload()" class="btn btn-secondary">Coba lagi</button>
@endsection
