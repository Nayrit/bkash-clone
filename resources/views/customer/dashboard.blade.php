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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-pink-500">
                <h3 class="text-lg font-medium text-gray-900 mb-1">Send Money (P2P)</h3>
                <p class="text-xs text-gray-500 mb-4">Fee: Flat ৳ {{ number_format(\App\Models\SystemSetting::getVal('send_money_fee_flat', 5.00), 2) }} per transfer (goes directly to Admin)</p>

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
                @php $cashOutFeeRate = (float) \App\Models\SystemSetting::getVal('cash_out_fee_percentage', 2.00); @endphp
                <p class="text-xs text-gray-500 mb-4">Fee: {{ $cashOutFeeRate }}% (৳{{ number_format($cashOutFeeRate * 10, 2) }} per ৳1,000)</p>

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

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{
            searchQuery: '',
            selectedTxn: null,
            openModal(txn) { this.selectedTxn = txn; },
            closeModal() { this.selectedTxn = null; },
            matchSearch(text) {
                if (!this.searchQuery) return true;
                return text.toLowerCase().includes(this.searchQuery.toLowerCase());
            }
        }">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
                <h3 class="text-lg font-medium text-gray-900">Transaction History & Details</h3>
                <div class="relative w-full sm:w-80">
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Search by date (YYYY-MM-DD), TXN ID, phone..." 
                           class="w-full pl-9 pr-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
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
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Fee</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Net Impact</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($transactions as $txn)
                                <tr x-show="matchSearch('{{ $txn->created_at->format('Y-m-d d M Y h:i A') }} {{ $txn->txn_id }} {{ $txn->type }} {{ $txn->sender->name ?? '' }} {{ $txn->sender->phone ?? '' }} {{ $txn->receiver->name ?? '' }} {{ $txn->receiver->phone ?? '' }} {{ $txn->amount }}')"
                                    @click="openModal({
                                    id: '{{ $txn->txn_id }}',
                                    date: '{{ $txn->created_at->format('d M Y, h:i:s A') }}',
                                    type: '{{ ucwords(str_replace('_', ' ', $txn->type)) }}',
                                    sender: '{{ $txn->sender->name ?? 'System' }} ({{ $txn->sender->phone ?? 'N/A' }})',
                                    receiver: '{{ $txn->receiver->name ?? 'System' }} ({{ $txn->receiver->phone ?? 'N/A' }})',
                                    amount: '{{ number_format($txn->amount, 2) }}',
                                    fee: '{{ number_format($txn->fee ?? 0, 2) }}',
                                    commission: '{{ number_format($txn->agent_commission ?? 0, 2) }}'
                                })" class="hover:bg-pink-50 cursor-pointer transition">
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

                <div x-show="selectedTxn !== null" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                     @click.self="closeModal()"
                     style="display: none;">
                    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 relative">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-4 mb-4">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-pink-600">Transaction Details</span>
                                <h4 class="text-lg font-bold text-gray-900 font-mono mt-0.5" x-text="selectedTxn ? selectedTxn.id : ''"></h4>
                            </div>
                            <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 rounded-full p-2 hover:bg-gray-100 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl">
                                <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase">Timestamp</p>
                                    <p class="font-medium text-gray-800 mt-1" x-text="selectedTxn ? selectedTxn.date : ''"></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase">Operation Type</p>
                                    <p class="font-bold text-pink-700 mt-1" x-text="selectedTxn ? selectedTxn.type : ''"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 border border-gray-100 p-4 rounded-xl">
                                <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase">Sender Account</p>
                                    <p class="font-semibold text-gray-800 mt-1" x-text="selectedTxn ? selectedTxn.sender : ''"></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase">Receiver Account</p>
                                    <p class="font-semibold text-gray-800 mt-1" x-text="selectedTxn ? selectedTxn.receiver : ''"></p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-pink-50 to-purple-50 p-4 rounded-xl space-y-2 border border-pink-100">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 font-medium">Principal Amount:</span>
                                    <span class="font-bold text-gray-900 text-base" x-text="selectedTxn ? '৳ ' + selectedTxn.amount : ''"></span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-500">Transaction Cost:</span>
                                    <span class="font-medium text-pink-700" x-text="selectedTxn ? '৳ ' + selectedTxn.fee : ''"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-2">
                            <button @click="closeModal()" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-semibold py-2.5 px-4 rounded-xl transition">
                                Close Details
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>