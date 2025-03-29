@extends('base.base')

@section('titre', 'Créer un(e) {{ ucfirst($tableName) }}')

@section('content')
<div class="container">
    <h1>Créer un(e) {{ ucfirst($tableName) }}</h1>

    <form action="{{ route(strtolower($tableName) . '.store') }}" method="POST">
        @csrf

        <!-- Génération dynamique des champs du formulaire -->
        @foreach ($columns as $column)
            @if (!in_array($column, ['id', 'created_at', 'updated_at']))
                <div class="form-group">
                    <label for="{{ $column }}">{{ ucfirst($column) }}</label>
                    <input type="text" name="{{ $column }}" id="{{ $column }}" class="form-control">
                </div>
            @endif
        @endforeach

        <button type="submit" class="btn btn-primary">Créer</button>
    </form>
</div>
@endsection