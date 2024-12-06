<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendez-vous confirmé</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { background-color: white; padding: 20px; border-radius: 8px; }
        .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
        .content { margin: 20px 0; }
        .footer { font-size: 0.9em; color: #555; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Rendez-vous confirmé</h1>
        </div>

        <div class="content">
            <p>Bonjour {{ $prestataire->name }},</p>
            <p>Un nouveau rendez-vous a été confirmé avec vous.</p>
            <p><strong>Client :</strong> {{ $rendezvous->client->name }}</p>
            <p><strong>Jour :</strong> {{ $rendezvous->disponobilite_id->jour }}</p>
            <p><strong>Heure de debut :</strong> {{ $rendezvous->heureDebut }}</p>
            <p><strong>Durée :</strong> {{ $rendezvous->duree }} minutes</p>

            <a href="{{ url('/rendezvous/' . $rendezvous->id) }}" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Voir les détails</a>
        </div>

        <div class="footer">
            <p>Merci de bien vouloir confirmer votre disponibilité.</p>
            <p>Cordialement, l'équipe de gestion des rendez-vous.</p>
        </div>
    </div>
</body>
</html>
