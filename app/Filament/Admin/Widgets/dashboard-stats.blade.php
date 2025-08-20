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

</div>
