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
                <div class="flex flex-wrap gap-8 mt-3">
                    <div>
                        <p class="text-xs text-gray-400 font-semibold uppercase">Digital Float Balance</p>
                        <p class="text-3xl font-bold text-blue-600 mt-0.5">
                            ৳ {{ number_format($agent->wallet->balance ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="border-l border-gray-200 pl-6">
                        <p class="text-xs text-gray-400 font-semibold uppercase">Physical Cash Collected</p>
                        <p class="text-3xl font-bold text-emerald-600 mt-0.5">
                            ৳ {{ number_format($agent->wallet->cash_in_hand ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="border-l border-gray-200 pl-6">
                        <p class="text-xs text-gray-400 font-semibold uppercase">Payable Due to Admin</p>
                        <p class="text-3xl font-bold text-purple-600 mt-0.5">
                            ৳ {{ number_format($agent->wallet->admin_due ?? 0, 2) }}
                        </p>
                    </div>
                </div>
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-green-500 flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Perform Cash-In</h3>
                    <p class="text-xs text-gray-500 mb-4">Deposit funds directly into a customer account. No fee / 0% commission.</p>

                    <form method="POST" action="{{ route('agent.cash.in') }}">
                        @csrf
                        <div class="mb-4">
                            <x-input-label for="customer_phone" :value="__('Customer Phone')" />
                            <x-text-input id="customer_phone" class="block mt-1 w-full" type="text" name="customer_phone" required placeholder="018..." />
                            <x-input-error :messages="$errors->get('customer_phone')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="cashin_amount" :value="__('Amount (BDT)')" />
                            <x-text-input id="cashin_amount" class="block mt-1 w-full" type="number" step="0.01" name="amount" min="10" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-primary-button class="bg-green-600 hover:bg-green-700 w-full justify-center">
                                {{ __('Perform Cash-In') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Request Float from Treasury -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-blue-500 flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Request Float</h3>
                    <p class="text-xs text-gray-500 mb-4">Request additional float from Admin Treasury.</p>

                    <form method="POST" action="{{ route('agent.request.funds') }}">
                        @csrf
                        <div class="mb-4">
                            <x-input-label for="amount" :value="__('Amount (BDT)')" />
                            <x-text-input id="amount" class="block mt-1 w-full" type="number" step="0.01" name="amount" min="500" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        <div class="mt-6 pt-16">
                            <x-primary-button class="bg-blue-600 hover:bg-blue-700 w-full justify-center">
                                {{ __('Submit Request') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Unified Step-by-Step Settlement Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-indigo-600 flex flex-col justify-between" x-data="{ settleAmount: '' }">
                <div>
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-medium text-gray-900">Settle Admin Dues</h3>
                        <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-800">PAY IN STEPS</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Pay Admin Treasury for cash collections. Pay partially or fully.</p>

                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 mb-4 flex justify-between items-center">
                        <div>
                            <p class="text-xs font-semibold text-indigo-700 uppercase">Current Due to Admin</p>
                            <p class="text-xl font-bold text-indigo-950 mt-0.5">৳ {{ number_format($agent->wallet->admin_due ?? 0, 2) }}</p>
                        </div>
                        @if(($agent->wallet->admin_due ?? 0) > 0)
                            <button type="button" @click="settleAmount = '{{ $agent->wallet->admin_due }}'" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-2.5 py-1.5 rounded-lg transition">
                                Pay Full Due
                            </button>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('agent.remit.admin') }}">
                        @csrf
                        <div class="mb-4">
                            <x-input-label for="remit_amount" :value="__('Amount to Pay Now (BDT)')" />
                            <x-text-input id="remit_amount" x-model="settleAmount" class="block mt-1 w-full" type="number" step="0.01" name="amount" min="1" required placeholder="Enter partial or full amount" />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 w-full justify-center">
                                {{ __('Settle Payment with Admin') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
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

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{
            selectedTxn: null,
            openModal(txn) { this.selectedTxn = txn; },
            closeModal() { this.selectedTxn = null; }
        }">
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
                                <tr @click="openModal({
                                    id: '{{ $txn->txn_id }}',
                                    date: '{{ $txn->created_at->format('d M Y, h:i:s A') }}',
                                    type: '{{ ucwords(str_replace('_', ' ', $txn->type)) }}',
                                    sender: '{{ $txn->sender->name ?? 'System' }} ({{ $txn->sender->phone ?? 'N/A' }})',
                                    receiver: '{{ $txn->receiver->name ?? 'System' }} ({{ $txn->receiver->phone ?? 'N/A' }})',
                                    amount: '{{ number_format($txn->amount, 2) }}',
                                    fee: '{{ number_format($txn->fee ?? 0, 2) }}',
                                    commission: '{{ number_format($txn->agent_commission ?? 0, 2) }}',
                                    admin_fee: '{{ number_format($txn->admin_fee ?? 0, 2) }}'
                                })" class="hover:bg-blue-50 cursor-pointer transition">
                                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $txn->created_at->format('d M Y, h:i:s A') }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $txn->txn_id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                                            @if($txn->type === 'cash_in') bg-green-100 text-green-800
                                            @elseif($txn->type === 'cash_out') bg-purple-100 text-purple-800
                                            @elseif($txn->type === 'commission') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            @if($txn->type === 'commission')
                                                Due Settlement
                                            @else
                                                {{ ucwords(str_replace('_', ' ', $txn->type)) }}
                                            @endif
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
                                    <td class="px-4 py-3 text-right text-xs text-gray-500">
                                        @if($txn->agent_commission > 0)
                                            <span class="text-green-700 font-bold">+৳{{ number_format($txn->agent_commission, 2) }}</span>
                                        @else
                                            ৳ {{ number_format($txn->fee ?? 0, 2) }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold">
                                        @if($txn->sender_id === $agent->id)
                                            <span class="text-red-600">- ৳ {{ number_format($txn->amount, 2) }}</span>
                                        @else
                                            <span class="text-green-600">+ ৳ {{ number_format($txn->amount + ($txn->agent_commission ?? 0), 2) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- POP-UP MODAL DIALOG -->
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
                                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Transaction Details</span>
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
                                    <p class="font-bold text-indigo-700 mt-1" x-text="selectedTxn ? selectedTxn.type : ''"></p>
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

                            <div class="bg-gradient-to-r from-indigo-50 to-blue-50 p-4 rounded-xl space-y-2 border border-indigo-100">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 font-medium">Principal Amount:</span>
                                    <span class="font-bold text-gray-900 text-base" x-text="selectedTxn ? '৳ ' + selectedTxn.amount : ''"></span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-500">Total Customer Fee:</span>
                                    <span class="font-medium text-gray-700" x-text="selectedTxn ? '৳ ' + selectedTxn.fee : ''"></span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-green-700 font-semibold">Agent Commission Share:</span>
                                    <span class="font-bold text-green-700" x-text="selectedTxn ? '৳ ' + selectedTxn.commission : ''"></span>
                                </div>
                                <div class="flex justify-between items-center text-xs border-t border-indigo-200/60 pt-2">
                                    <span class="text-indigo-700 font-semibold">Admin Treasury Share:</span>
                                    <span class="font-bold text-indigo-700" x-text="selectedTxn ? '৳ ' + selectedTxn.admin_fee : ''"></span>
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