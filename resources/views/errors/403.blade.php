@extends('errors.layout')

@section('code', '403')
@section('headline', 'Forbidden')
@section('detail', isset($exception) ? $exception->getMessage() : 'You do not have permission to access this resource.')
