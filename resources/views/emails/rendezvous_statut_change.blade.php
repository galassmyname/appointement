<!DOCTYPE html>
<html>
<head>
    <title>Changement de statut de votre rendez-vous</title>
</head>
<body>
    <h1>Bonjour {{ $user->name }},</h1>

    <p>{{ $message }}</p> <!-- Affiche le message spécifique -->

    <p><strong>Rendez-vous : </strong>{{ $rendezVous->heureDebut }} - {{ $rendezVous->heureFin }}</p>
    <p><strong>Statut : </strong>{{ $rendezVous->statut }}</p>

    <p>Merci de vérifier les détails de votre rendez-vous.</p>
</body>
</html>
