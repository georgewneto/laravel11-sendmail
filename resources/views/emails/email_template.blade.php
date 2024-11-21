@extends('emails.layout')

@section('title', $data['subject'])
@section('header')
@endsection

@section('content')
    <p>{{$data['message']}}</p>
@endsection

@section('footer')

@endsection
