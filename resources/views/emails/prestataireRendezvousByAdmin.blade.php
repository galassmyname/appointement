<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendez-vous confirmé</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #F6F6F8;
            margin: 0;
            padding: 0;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            max-width: 650px;
            margin: 20px auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #9BEBD6FF;
            color: #009688; /* Ajout du '#' pour la couleur */
            padding: 5px 10px; /* Réduction du padding */
            display: flex;
            align-items: center;
            justify-content: flex-start;
            height: 40px; /* Définir une hauteur fixe, si nécessaire */
            max-height: 40px; /* Limiter la hauteur maximale */
        }


        .header img {
            max-width: 60px;
            margin-right: 15px; /* Espace entre l'image et le texte */
        }

        .header h2 {
            font-size: 24px;
            font-weight: 600;
            margin: 0; /* Retirer les marges par défaut */
        }

        .icon {
            background-color: #009688;
            border-radius: 50%;
            color: white;
            margin: 20px auto;
            display: flex; /* Utiliser flexbox pour centrer */
            justify-content: center; /* Centrer horizontalement */
            align-items: center; /* Centrer verticalement */
            width: 60px;
            height: 60px;
            font-size: 30px;
            text-align: center;
        }

        .content {
            padding: 30px;
            color: #333;
            background-color: #ffffff;
            text-align: left;
        }
        .content p {
            font-size: 16px;
            line-height: 1.6;
            margin-left: 20px; /* Ajoute une marge à gauche de 20px */
        }

        .content strong {
            color: #009688;
        }
        .details {
            margin: 20px 0;
            margin-left: 20px;
        }
        .details li {
            margin-bottom: 10px;
            font-size: 15px;
        }
        .footer {
            background-color: #DCFAFAFF;
            text-align: center;
            padding: 20px;
            width: 100%;
            font-size: 14px;
            color: #555;
            border-top: 1px solid #ddd; /* Ligne séparatrice pour démarquer le footer */
        }

        .social-icons {
            margin: 15px 0;
            display: flex;
            justify-content: center;
            gap: 15px; /* Espacement entre les icônes */
        }

        .social-icons img {
            width: 30px; /* Taille uniforme des icônes */
            height: 30px;
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s ease; /* Ajout d'un effet au survol */
        }

        .social-icons img:hover {
            transform: scale(1.2); /* Zoomer légèrement au survol */
            opacity: 0.8; /* Réduction de l'opacité au survol */
        }

        .footer p {
            margin: 5px 0; /* Espacement entre les paragraphes */
        }

        @media (max-width: 600px) {
            .container {
                padding: 10px;
                max-width: 100%;
            }
            .header h2 {
                font-size: 20px;
            }
            .content p {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
        <img src="{{ asset('storage/images/bakeli_logo.png') }}" alt="Logo de l'entreprise">
            <h2>Creation de Rendez-vous</h2>
        </div>

        <!-- Contenu principal -->
        <div class="content">
            <div class="icon">📅</div>
            <p>Bonjour <strong>{{ $rendezvous->prestataire->name }}</strong>,</p>
            <p>Un nouveau rendez-vous a été créé avec vous par l'administrateur.<br> Voici les détails du rendez-vous :</p>

            <div class="details">
                <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                    <tr>
                        <th style="text-align: left; border: 1px solid #ddd; padding: 8px; background-color: #009688; color: white;">Détail</th>
                        <th style="text-align: left; border: 1px solid #ddd; padding: 8px; background-color: #009688; color: white;">Valeur</th>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><strong>Client</strong></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $rendezvous->client->name }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><strong>Email du client</strong></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $rendezvous->client->email }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><strong>Téléphone du client</strong></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $rendezvous->client->telephone }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><strong>Jour de disponibilité</strong></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $rendezvous->disponibilite->jour }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><strong>Date et heure de début</strong></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $rendezvous->heureDebut }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><strong>Date et heure de fin</strong></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $rendezvous->heureFin }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><strong>Type de rendez-vous</strong></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $rendezvous->type_rendezvous->nomService }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><strong>Durée</strong></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $rendezvous->duree }} minutes</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><strong>Statut</strong></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $rendezvous->status }}</td>
                    </tr>
                </table>
            </div>

            <p style="text-align: center; margin-top: 20px;">
                Merci de confirmer votre disponibilité en cas de changement.
            </p>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>Cordialement, l'équipe de gestion des rendez-vous.</p>
            <div class="social-icons">
            <img src="{{ asset('storage/images/image.png') }}" aria-label="Facebook" class="fab fa-facebook">
            <img src="{{ asset('storage/images/twitter.jpg') }}"  aria-label="Twitter" class="fab fa-twitter">
            <img src="{{ asset('storage/images/linkdin.png') }}" aria-label="LinkedIn" class="fab fa-linkedin">>
            </div>
            <p>Sénégal, Dakar, RB 12345</p>
            <p>(+221) 77 777 77 77 | info@bakeliwork.com</p>
        </div>
    </div>
</body>
</html>
