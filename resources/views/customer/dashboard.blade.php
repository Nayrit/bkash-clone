<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Wallet') }}
        </h2>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-pink-500 p-6 flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <div class="flex items-center space-x-3">
                    <h3 class="text-lg font-medium text-gray-900">{{ $user->name }}</h3>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-pink-100 text-pink-800 uppercase tracking-wide">Customer • {{ $user->phone }}</span>
                </div>
                <p class="text-4xl font-bold text-pink-600 mt-2">
                    ৳ {{ number_format($user->wallet->balance ?? 0, 2) }}
                </p>
            </div>
            <div class="mt-4 md:mt-0 text-right text-xs text-gray-500">
                <p>Account ID: #{{ $user->id }}</p>
                <p>Registered Phone: <strong class="text-gray-800 text-sm">{{ $user->phone }}</strong></p>
            </div>
        </div>

        @if (session('status'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
                <p class="font-medium text-sm text-green-700">{{ session('status') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Send Money Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Send Money (P2P)</h3>

                <form method="POST" action="{{ route('customer.send.money') }}">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="phone" :value="__('Recipient Phone Number')" />
                        <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" required placeholder="e.g. 01712345678" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="amount" :value="__('Amount (BDT)')" />
                        <x-text-input id="amount" class="block mt-1 w-full" type="number" step="0.01" name="amount" min="10" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-primary-button class="bg-pink-600 hover:bg-pink-700">
                            {{ __('Send Money') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Cash Out Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-purple-500">
                <h3 class="text-lg font-medium text-gray-900 mb-1">Cash Out to Agent</h3>
                <p class="text-xs text-gray-500 mb-4">Fee: {{ \App\Models\SystemSetting::getVal('cash_out_fee_percentage', 2.00) }}% (৳20 per ৳1,000)</p>

                <form method="POST" action="{{ route('customer.cash.out') }}">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="agent_phone" :value="__('Agent Phone Number')" />
                        <x-text-input id="agent_phone" class="block mt-1 w-full" type="text" name="agent_phone" required placeholder="e.g. 01700000001" />
                        <x-input-error :messages="$errors->get('agent_phone')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="cash_out_amount" :value="__('Amount (BDT)')" />
                        <x-text-input id="cash_out_amount" class="block mt-1 w-full" type="number" step="0.01" name="amount" min="50" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-primary-button class="bg-purple-600 hover:bg-purple-700">
                            {{ __('Cash Out') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Comprehensive Recent Transactions Table -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Detailed Transaction History</h3>
            
            @if($transactions->isEmpty())
                <p class="text-gray-500 italic">No transactions yet.</p>
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
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Fee</th>
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
                                            @elseif($txn->type === 'send_money') bg-pink-100 text-pink-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucwords(str_replace('_', ' ', $txn->type)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        @if($txn->sender_id === $user->id)
                                            To: <strong>{{ $txn->receiver->name ?? 'N/A' }}</strong> ({{ $txn->receiver->phone ?? 'N/A' }})
                                        @else
                                            From: <strong>{{ $txn->sender->name ?? 'N/A' }}</strong> ({{ $txn->sender->phone ?? 'N/A' }})
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">৳ {{ number_format($txn->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-xs text-gray-500">৳ {{ number_format($txn->fee ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-bold">
                                        @if($txn->sender_id === $user->id)
                                            <span class="text-red-600">- ৳ {{ number_format($txn->amount + ($txn->fee ?? 0), 2) }}</span>
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