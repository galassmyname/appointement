<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Votre mot de passe temporaire</title>
</head>
<body>
    <p>Bonjour {{ $name }},</p>

    <p>Votre compte de prestataire a été créé avec succès. Voici votre mot de passe temporaire :</p>
    <p><strong>{{ $password }}</strong></p>

    <p>Nous vous recommandons de changer ce mot de passe dès votre première connexion.</p>

    <p>Cordialement,</p>
    <p>L'équipe de gestion.</p>
</body>
</html>
