<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Central Treasury Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-green-500 p-6 flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <div class="flex items-center space-x-3">
                        <h3 class="text-lg font-medium text-gray-900">{{ $admin->name }}</h3>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 uppercase tracking-wide">System Admin • {{ $admin->phone }}</span>
                    </div>
                    <p class="text-4xl font-bold text-green-600 mt-2">
                        ৳ {{ number_format($admin->wallet->balance ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Master System Float (Treasury Reserve)</p>
                </div>
                <div class="mt-4 md:mt-0 text-right text-xs text-gray-500">
                    <p>Admin ID: #{{ $admin->id }}</p>
                    <p>Registered Phone: <strong class="text-gray-800 text-sm">{{ $admin->phone }}</strong></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                <div class="bg-white p-5 rounded-lg shadow border border-gray-100">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Users</h4>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalUsers }}</p>
                </div>
                
                <div class="bg-white p-5 rounded-lg shadow border border-gray-100">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Digital Float</h4>
                    <p class="text-2xl font-bold text-green-600 mt-1">৳ {{ number_format($totalSystemBalance, 2) }}</p>
                </div>

                <div class="bg-white p-5 rounded-lg shadow border border-gray-100">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Agent Cash In Hand</h4>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">৳ {{ number_format($totalAgentCashInHand ?? 0, 2) }}</p>
                </div>

                <div class="bg-white p-5 rounded-lg shadow border border-gray-100">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Agent Dues Payable</h4>
                    <p class="text-2xl font-bold text-purple-600 mt-1">৳ {{ number_format($totalAdminDue ?? 0, 2) }}</p>
                </div>

                <div class="bg-white p-5 rounded-lg shadow border border-gray-100">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Transactions</h4>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ $totalTransactions }}</p>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Float</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
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
                                            <form method="POST" action="{{ route('admin.request.approve', $request->id) }}">
                                                @csrf
                                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow">
                                                    Approve Agent
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <!-- DYNAMIC FEE & COMMISSION MANAGEMENT CARD -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-indigo-600">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Platform Fee & Commission Structure</h3>
                            <p class="text-xs text-gray-500">Configure real-time transaction fees and agent commissions across the ecosystem. Changes apply instantly.</p>
                        </div>
                        <span class="mt-2 sm:mt-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            Dynamic System Settings
                        </span>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.update') }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <x-input-label for="send_money_fee_flat" :value="__('Send Money Flat Fee (BDT)')" />
                                <x-text-input id="send_money_fee_flat" class="block mt-1 w-full" type="number" step="0.01" min="0" name="send_money_fee_flat" :value="$sendMoneyFeeFlat ?? '5.00'" required />
                                <p class="text-xs text-gray-400 mt-1">Charged on P2P transfers (100% Admin revenue).</p>
                                <x-input-error :messages="$errors->get('send_money_fee_flat')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="cash_out_fee_percentage" :value="__('Cash-Out Total Fee Rate (%)')" />
                                <x-text-input id="cash_out_fee_percentage" class="block mt-1 w-full" type="number" step="0.01" min="0" max="100" name="cash_out_fee_percentage" :value="$cashOutFeePercent ?? '2.00'" required />
                                <p class="text-xs text-gray-400 mt-1">Total fee charged to customer on withdrawal.</p>
                                <x-input-error :messages="$errors->get('cash_out_fee_percentage')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="agent_commission_percentage" :value="__('Agent Cash-Out Commission (%)')" />
                                <x-text-input id="agent_commission_percentage" class="block mt-1 w-full" type="number" step="0.01" min="0" max="100" name="agent_commission_percentage" :value="$agentCommissionPercent ?? '1.50'" required />
                                <p class="text-xs text-gray-400 mt-1">Agent earnings out of the Cash-Out fee.</p>
                                <x-input-error :messages="$errors->get('agent_commission_percentage')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-between">
                            <div class="text-xs text-gray-600 bg-gray-50 px-3 py-2 rounded-lg border border-gray-200">
                                <strong>Net Admin Treasury Share on Cash-Out:</strong>
                                <span class="text-indigo-700 font-bold">{{ number_format(((float)($cashOutFeePercent ?? 2.00) - (float)($agentCommissionPercent ?? 1.50)), 2) }}%</span>
                                <span class="text-gray-400">(Total Fee % minus Agent Commission %)</span>
                            </div>
                            <x-primary-button class="bg-indigo-600 hover:bg-indigo-700">
                                {{ __('Update Platform Fees') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Global System Ledger & Audit Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="{
                searchQuery: '',
                selectedTxn: null,
                openModal(txn) { this.selectedTxn = txn; },
                closeModal() { this.selectedTxn = null; },
                matchSearch(text) {
                    if (!this.searchQuery) return true;
                    return text.toLowerCase().includes(this.searchQuery.toLowerCase());
                }
            }">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
                        <h3 class="text-lg font-medium text-gray-900">Global System Ledger & Audit Log</h3>
                        <div class="relative w-full sm:w-80">
                            <input type="text" 
                                   x-model="searchQuery" 
                                   placeholder="Search by date (YYYY-MM-DD or d M Y), TXN ID, phone..." 
                                   class="w-full pl-9 pr-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                    @if($recentTransactions->isEmpty())
                        <p class="text-gray-500 italic text-sm">No transactions recorded across the system yet.</p>
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
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recentTransactions as $txn)
                                        <tr x-show="matchSearch('{{ $txn->created_at->format('Y-m-d d M Y h:i A') }} {{ $txn->txn_id }} {{ $txn->type }} {{ $txn->sender->name ?? '' }} {{ $txn->sender->phone ?? '' }} {{ $txn->receiver->name ?? '' }} {{ $txn->receiver->phone ?? '' }} {{ $txn->amount }}')"
                                            @click="openModal({
                                            id: '{{ $txn->txn_id }}',
                                            date: '{{ $txn->created_at->format('d M Y, h:i:s A') }}',
                                            type: '{{ ucwords(str_replace('_', ' ', $txn->type)) }}',
                                            sender: '{{ $txn->sender->name ?? 'System' }} ({{ $txn->sender->phone ?? 'N/A' }})',
                                            receiver: '{{ $txn->receiver->name ?? 'System' }} ({{ $txn->receiver->phone ?? 'N/A' }})',
                                            amount: '{{ number_format($txn->amount, 2) }}',
                                            fee: '{{ number_format($txn->fee ?? 0, 2) }}',
                                            commission: '{{ number_format($txn->agent_commission ?? 0, 2) }}',
                                            admin_fee: '{{ number_format($txn->admin_fee ?? 0, 2) }}'
                                        })" class="hover:bg-green-50 cursor-pointer transition">
                                            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $txn->created_at->format('d M Y, h:i:s A') }}</td>
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
                                                <strong>{{ $txn->sender->name ?? 'System' }}</strong><br>
                                                <span class="text-gray-500">{{ $txn->sender->phone ?? 'N/A' }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-xs">
                                                <strong>{{ $txn->receiver->name ?? 'System' }}</strong><br>
                                                <span class="text-gray-500">{{ $txn->receiver->phone ?? 'N/A' }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-medium">৳ {{ number_format($txn->amount, 2) }}</td>
                                            <td class="px-4 py-3 text-right text-xs text-gray-500">৳ {{ number_format($txn->fee ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>