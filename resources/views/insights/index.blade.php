@extends('layouts.app')

@section('title', 'Keputusan Strategis')

@section('content')
@include('insights.partials.index.header')
@include('insights.partials.index.filters')
@include('insights.partials.index.styles')
@include('insights.partials.index.cards')
@endsection