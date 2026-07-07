<x-app-layout>
    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white p-6 shadow-sm rounded-lg">
            <h3 class="text-lg font-bold mb-4">Global Audit Trail</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs uppercase">TXN ID</th>
                        <th class="px-6 py-3 text-left text-xs uppercase">From</th>
                        <th class="px-6 py-3 text-left text-xs uppercase">To</th>
                        <th class="px-6 py-3 text-left text-xs uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($transactions as $txn)
                    <tr>
                        <td class="px-6 py-4 font-mono text-xs">{{ $txn->txn_id }}</td>
                        <td class="px-6 py-4">{{ $txn->sender->name ?? 'System' }}</td>
                        <td class="px-6 py-4">{{ $txn->receiver->name ?? 'System' }}</td>
                        <td class="px-6 py-4 font-bold text-green-600">৳ {{ number_format($txn->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $transactions->links() }}</div>
        </div>
    </div>
</x-app-layout>