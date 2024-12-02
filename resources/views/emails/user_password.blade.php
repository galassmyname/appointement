@component('mail::message')
# Bonjour {{ $name }},

Un compte a été créé pour vous sur notre plateforme. Voici vos informations de connexion :

**Email :** {{ $email }}  
**Mot de passe :** {{ $password }}

Nous vous recommandons de changer ce mot de passe après votre première connexion.

@component('mail::button', ['url' => url('/login')])
Se connecter
@endcomponent

Merci,  
L'équipe {{ config('app.name') }}
@endcomponent
