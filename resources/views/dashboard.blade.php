<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Nombre de Rendez-vous -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-gray-700">Nombre de Rendez-vous</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $totalRendezvous }}</p>
                </div>

                <!-- Nombre de Prestataires -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-gray-700">Nombre de Prestataires</h3>
                    <p class="text-2xl font-bold text-green-600">{{ $totalPrestataires }}</p>
                </div>

                <!-- Nombre de Clients -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-gray-700">Nombre de Clients</h3>
                    <p class="text-2xl font-bold text-purple-600">{{ $totalClients }}</p>
                </div>

                <!-- Taux de Rendez-vous Validés -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-gray-700">Taux de Rendez-vous Validés</h3>
                    <p class="text-2xl font-bold text-yellow-600">{{ $validationRate }}%</p>
                </div>

                <!-- Taux de Rendez-vous Annulés -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-gray-700">Taux de Rendez-vous Annulés</h3>
                    <p class="text-2xl font-bold text-red-600">{{ $cancellationRate }}%</p>
                </div>

                <!-- Autres statistiques -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-gray-700">Autres Statistiques</h3>
                    <p class="text-xl">Affichez d'autres statistiques personnalisées ici.</p>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>