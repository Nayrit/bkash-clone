<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Agent Liquidity Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-blue-500 p-6">
            <h3 class="text-lg font-medium text-gray-900">Current Digital Float</h3>
            <p class="text-4xl font-bold text-blue-600 mt-2">
                ৳ {{ number_format($agent->wallet->balance ?? 0, 2) }}
            </p>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Request Float from Treasury</h3>
            
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('agent.request.funds') }}">
                @csrf
                <div>
                    <x-input-label for="amount" :value="__('Amount (BDT)')" />
                    <x-text-input id="amount" class="block mt-1 w-full md:w-1/3" type="number" name="amount" min="500" required />
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>
                <div class="mt-4">
                    <x-primary-button>
                        {{ __('Submit Request') }}
                    </x-primary-button>
                </div>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Request History</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($fundingRequests as $request)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $request->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-6 py-4 font-bold">৳ {{ number_format($request->amount, 2) }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $request->status === 'approved' ? 'bg-green-100 text-green-800' : ($request->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>