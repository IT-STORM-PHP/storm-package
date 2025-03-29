@extends('base.base')

@section('titre', 'Modifier un(e) {{ ucfirst($tableName) }}')

@section('content')
<div class="container">
    <h1>Modifier un(e) {{ ucfirst($tableName) }}</h1>

    <form action="{{ route(strtolower($tableName) . '.update', $item->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Génération dynamique des champs du formulaire -->
        @foreach ($columns as $column)
            @if (!in_array($column, ['id', 'created_at', 'updated_at']))
                <div class="form-group">
                    <label for="{{ $column }}">{{ ucfirst($column) }}</label>
                    <input type="text" name="{{ $column }}" id="{{ $column }}" class="form-control" value="{{ $item->$column }}">
                </div>
            @endif
        @endforeach

        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </form>
</div>
@endsection