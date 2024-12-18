<h1>Bonjour {{ $rendezVous->client->name }}</h1>
<p>Nous vous rappelons que vous avez un rendez-vous programmé :</p>
<ul>
    <li>Date : {{ $rendezVous->jour }}</li>
    <li>Heure : {{ $rendezVous->heureDebut }}</li>
</ul>
<p>Veuillez vous préparer au moins 60 minutes avant le début du rendez-vous.</p>
