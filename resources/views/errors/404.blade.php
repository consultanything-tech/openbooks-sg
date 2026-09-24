@extends('errors.layout')

@section('code', '404')
@section('headline', 'Page not found')
@section('detail', isset($exception) ? $exception->getMessage() : 'The page you are looking for does not exist or has been moved.')
