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

            <!-- Global System Ledger & Audit Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="{
                selectedTxn: null,
                openModal(txn) { this.selectedTxn = txn; },
                closeModal() { this.selectedTxn = null; }
            }">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Global System Ledger & Audit Log</h3>
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
                                                    {{ ucwords(str_replace('_', ' ', $txn->type)) }}
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