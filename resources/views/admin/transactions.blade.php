<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Global Audit Trail & System Ledger') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Search and audit every transaction across the entire platform ecosystem.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="mt-2 sm:mt-0 inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg shadow-sm transition">
                &larr; Back to Admin Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white p-6 shadow-sm rounded-xl border border-gray-100" x-data="{
            searchQuery: '{{ request('q', '') }}',
            selectedTxn: null,
            openModal(txn) {
                this.selectedTxn = txn;
            },
            closeModal() {
                this.selectedTxn = null;
            }
        }">
            <!-- SEARCH FORM -->
            <form method="GET" action="{{ route('admin.transactions') }}" class="mb-6">
                <div class="flex flex-col lg:flex-row gap-3">
                    <div class="relative flex-1">
                        <input type="text" 
                               name="q" 
                               value="{{ request('q') }}"
                               x-model="searchQuery"
                               placeholder="Search by date (YYYY-MM-DD), TXN ID, phone, name, type, amount..." 
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" title="Start Date" class="py-2.5 px-3 border border-gray-300 rounded-xl text-sm text-gray-600 focus:ring-2 focus:ring-indigo-500">
                        <span class="text-gray-400 text-xs">to</span>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" title="End Date" class="py-2.5 px-3 border border-gray-300 rounded-xl text-sm text-gray-600 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm shadow transition">
                        Search & Filter
                    </button>
                    @if(request('q') || request('start_date') || request('end_date'))
                        <a href="{{ route('admin.transactions') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl text-sm transition">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            @if($transactions->isEmpty())
                <div class="text-center py-12">
                    <p class="text-gray-500 italic text-sm">No transactions match your search criteria.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Timestamp</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">TXN ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sender</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Receiver</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Fee</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Agent Comm.</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Admin Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($transactions as $txn)
                                <tr @click="openModal({
                                    id: '{{ $txn->txn_id }}',
                                    date: '{{ $txn->created_at->format('d M Y, h:i:s A') }}',
                                    type: '{{ $txn->type === 'commission' ? 'Due Settlement' : ucwords(str_replace('_', ' ', $txn->type)) }}',
                                    sender: '{{ $txn->sender->name ?? 'System' }} ({{ $txn->sender->phone ?? 'N/A' }})',
                                    receiver: '{{ $txn->receiver->name ?? 'System' }} ({{ $txn->receiver->phone ?? 'N/A' }})',
                                    amount: '{{ number_format($txn->amount, 2) }}',
                                    fee: '{{ number_format($txn->fee ?? 0, 2) }}',
                                    commission: '{{ number_format($txn->agent_commission ?? 0, 2) }}',
                                    admin_fee: '{{ number_format($txn->admin_fee ?? 0, 2) }}'
                                })" class="hover:bg-indigo-50/50 cursor-pointer transition">
                                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $txn->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $txn->txn_id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                                            @if($txn->type === 'cash_in') bg-green-100 text-green-800
                                            @elseif($txn->type === 'cash_out') bg-purple-100 text-purple-800
                                            @elseif($txn->type === 'system_float') bg-blue-100 text-blue-800
                                            @elseif($txn->type === 'commission') bg-indigo-100 text-indigo-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            @if($txn->type === 'commission')
                                                Due Settlement
                                            @else
                                                {{ ucwords(str_replace('_', ' ', $txn->type)) }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div class="font-medium text-gray-900">{{ $txn->sender->name ?? 'System' }}</div>
                                        <div class="text-gray-400">{{ $txn->sender->phone ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div class="font-medium text-gray-900">{{ $txn->receiver->name ?? 'System' }}</div>
                                        <div class="text-gray-400">{{ $txn->receiver->phone ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">৳ {{ number_format($txn->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-xs text-gray-500">৳ {{ number_format($txn->fee ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-xs text-green-600 font-medium">৳ {{ number_format($txn->agent_commission ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-xs text-indigo-600 font-bold">৳ {{ number_format($txn->admin_fee ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6">{{ $transactions->links() }}</div>
            @endif

            <!-- MODAL FOR TRANSACTION DETAILS -->
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
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Audit Trail Record</span>
                            <h3 class="text-lg font-bold text-gray-900 mt-0.5" x-text="selectedTxn?.type"></h3>
                        </div>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between py-1 border-b border-gray-50"><span class="text-gray-500">Transaction ID</span><span class="font-mono font-semibold text-gray-800" x-text="selectedTxn?.id"></span></div>
                        <div class="flex justify-between py-1 border-b border-gray-50"><span class="text-gray-500">Timestamp</span><span class="text-gray-800" x-text="selectedTxn?.date"></span></div>
                        <div class="flex justify-between py-1 border-b border-gray-50"><span class="text-gray-500">Sender Account</span><span class="font-medium text-gray-900" x-text="selectedTxn?.sender"></span></div>
                        <div class="flex justify-between py-1 border-b border-gray-50"><span class="text-gray-500">Receiver Account</span><span class="font-medium text-gray-900" x-text="selectedTxn?.receiver"></span></div>
                        <div class="flex justify-between py-1 border-b border-gray-50"><span class="text-gray-500">Base Transfer Amount</span><span class="font-bold text-gray-900" x-text="'৳ ' + selectedTxn?.amount"></span></div>
                        <div class="flex justify-between py-1 border-b border-gray-50"><span class="text-gray-500">Total Customer Fee</span><span class="text-gray-700" x-text="'৳ ' + selectedTxn?.fee"></span></div>
                        <div class="flex justify-between py-1 border-b border-gray-50"><span class="text-gray-500">Agent Commission Earned</span><span class="font-semibold text-green-700" x-text="'৳ ' + selectedTxn?.commission"></span></div>
                        <div class="flex justify-between py-1"><span class="text-gray-500">Net Admin Treasury Revenue</span><span class="font-bold text-indigo-600" x-text="'৳ ' + selectedTxn?.admin_fee"></span></div>
                    </div>
                    <div class="mt-6">
                        <button @click="closeModal()" class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold rounded-xl text-sm transition">Close Details</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>