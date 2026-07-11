<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Agent Liquidity Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-blue-500 p-6 flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <div class="flex items-center space-x-3">
                    <h3 class="text-lg font-medium text-gray-900">{{ $agent->name }}</h3>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 uppercase tracking-wide">Agent • {{ $agent->phone }}</span>
                </div>
                <p class="text-4xl font-bold text-blue-600 mt-2">
                    ৳ {{ number_format($agent->wallet->balance ?? 0, 2) }}
                </p>
            </div>
            <div class="mt-4 md:mt-0 text-right text-xs text-gray-500">
                <p>Agent ID: #{{ $agent->id }}</p>
                <p>Registered Phone: <strong class="text-gray-800 text-sm">{{ $agent->phone }}</strong></p>
            </div>
        </div>

        @if (session('status'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
                <p class="font-medium text-sm text-green-700">{{ session('status') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Customer Cash-In (Deposit) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-green-500">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Customer Cash-In</h3>
                <p class="text-xs text-gray-500 mb-4">Deposit digital float directly to a Customer account.</p>

                <form method="POST" action="{{ route('agent.cash.in') }}">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="customer_phone" :value="__('Customer Phone')" />
                        <x-text-input id="customer_phone" class="block mt-1 w-full" type="text" name="customer_phone" required placeholder="017..." />
                        <x-input-error :messages="$errors->get('customer_phone')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="cash_in_amount" :value="__('Amount (BDT)')" />
                        <x-text-input id="cash_in_amount" class="block mt-1 w-full" type="number" step="0.01" name="amount" min="20" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-primary-button class="bg-green-600 hover:bg-green-700 w-full justify-center">
                            {{ __('Perform Cash-In') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Request Float from Treasury -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-blue-500">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Request Float</h3>
                <p class="text-xs text-gray-500 mb-4">Request additional float from Admin Treasury.</p>

                <form method="POST" action="{{ route('agent.request.funds') }}">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="amount" :value="__('Amount (BDT)')" />
                        <x-text-input id="amount" class="block mt-1 w-full" type="number" step="0.01" name="amount" min="500" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>

                    <div class="mt-4 pt-16">
                        <x-primary-button class="bg-blue-600 hover:bg-blue-700 w-full justify-center">
                            {{ __('Submit Request') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Return Float to Treasury (Cash Out) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-purple-500">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Return Float to Treasury</h3>
                <p class="text-xs text-gray-500 mb-4">Cash-out excess digital float back to Admin.</p>

                <form method="POST" action="{{ route('agent.cash.out') }}">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="cash_out_amount_agent" :value="__('Amount (BDT)')" />
                        <x-text-input id="cash_out_amount_agent" class="block mt-1 w-full" type="number" step="0.01" name="amount" min="500" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>

                    <div class="mt-4 pt-16">
                        <x-primary-button class="bg-purple-600 hover:bg-purple-700 w-full justify-center">
                            {{ __('Return Float') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Funding Request History</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($fundingRequests as $request)
                        <tr>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $request->created_at->format('d M, h:i A') }}</td>
                            <td class="px-4 py-3 font-bold text-sm">৳ {{ number_format($request->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $request->status === 'approved' ? 'bg-green-100 text-green-800' : ($request->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-sm text-gray-500 text-center italic">No float requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Agent Ledger & Detailed Transactions</h3>
            @if($transactions->isEmpty())
                <p class="text-gray-500 italic text-sm">No transactions yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date & Time</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">TXN ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Counterparty</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Principal</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Fee / Comm.</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Net Impact</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($transactions as $txn)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $txn->created_at->format('d M Y, h:i:s A') }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $txn->txn_id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                                            @if($txn->type === 'cash_in') bg-green-100 text-green-800
                                            @elseif($txn->type === 'cash_out') bg-purple-100 text-purple-800
                                            @elseif($txn->type === 'commission') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucwords(str_replace('_', ' ', $txn->type)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        @if($txn->sender_id === $agent->id)
                                            To: <strong>{{ $txn->receiver->name ?? 'N/A' }}</strong> ({{ $txn->receiver->phone ?? 'N/A' }})
                                        @else
                                            From: <strong>{{ $txn->sender->name ?? 'N/A' }}</strong> ({{ $txn->sender->phone ?? 'N/A' }})
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">৳ {{ number_format($txn->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-xs text-gray-500">৳ {{ number_format($txn->fee ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-bold">
                                        @if($txn->sender_id === $agent->id)
                                            <span class="text-red-600">- ৳ {{ number_format($txn->amount, 2) }}</span>
                                        @else
                                            <span class="text-green-600">+ ৳ {{ number_format($txn->amount, 2) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>