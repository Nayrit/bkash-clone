<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Central Treasury Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-green-500">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900">Master System Float</h3>
                    <p class="text-4xl font-bold text-green-600 mt-2">
                        ৳ {{ number_format($admin->wallet->balance ?? 0, 2) }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">Total digital currency held in the central reserve.</p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Pending Agent Float Requests</h3>
                    
                    @if($pendingRequests->isEmpty())
                        <p class="text-gray-500 italic">No pending requests at this time.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($pendingRequests as $request)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ $request->agent->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $request->agent->phone }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-700">
                                            ৳ {{ number_format($request->amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-blue-600 font-semibold">Approval logic coming next...</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>