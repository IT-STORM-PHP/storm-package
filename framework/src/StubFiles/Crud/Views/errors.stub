@extends('base.base')

@section('titre', 'Erreur')

@section('content')
<div class="container">
    <div class="alert alert-danger mt-5">
        <h1 class="display-4">Erreur</h1>
        <p class="lead">Une erreur s'est produite lors du traitement de votre requête.</p>
        <hr class="my-4">
        <p>Veuillez réessayer ou contacter l'administrateur si le problème persiste.</p>
        <a href="{{ route('{{ strtolower($tableName) }}.index') }}" class="btn btn-primary">Retour à l'accueil</a>
    </div>
</div>
@endsection