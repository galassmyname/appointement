<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre mot de passe</title>
</head>
<body>
    <h1>Bonjour {{ $user->name }}</h1>
    <p>Voici votre mot de passe temporaire pour vous connecter :</p>
    <p><strong>{{ $password }}</strong></p>
    <p>Vous pouvez modifier ce mot de passe une fois connecté.</p>
</body>
</html>
