@extends('base.base')

@section('titre', 'Détails de {{ ucfirst($tableName) }}')

@section('content')
<div class="container">
    <h1>Détails de {{ ucfirst($tableName) }}</h1>

    <table class="table">
        <tbody>
            <!-- Génération dynamique des champs -->
            @foreach ($columns as $column)
                <tr>
                    <th>{{ ucfirst($column) }}</th>
                    <td>{{ $item->$column }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route(strtolower($tableName) . '.index') }}" class="btn btn-primary">Retour à la liste</a>
</div>
@endsection