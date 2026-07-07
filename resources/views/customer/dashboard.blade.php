<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Wallet') }}
        </h2>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-pink-500 p-6">
            <h3 class="text-lg font-medium text-gray-900">Current Balance</h3>
            <p class="text-4xl font-bold text-pink-600 mt-2">
                ৳ {{ number_format($user->wallet->balance ?? 0, 2) }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Send Money</h3>
                
                @if (session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600 bg-green-100 p-3 rounded">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('customer.send.money') }}">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="phone" :value="__('Recipient Phone Number')" />
                        <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" required placeholder="e.g. 01712345678" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="amount" :value="__('Amount (BDT)')" />
                        <x-text-input id="amount" class="block mt-1 w-full" type="number" name="amount" min="10" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-primary-button class="bg-pink-600 hover:bg-pink-700">
                            {{ __('Send Now') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Transactions</h3>
                
                @if($transactions->isEmpty())
                    <p class="text-gray-500 italic">No transactions yet.</p>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach($transactions as $txn)
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $txn->type === 'send_money' ? 'Send Money' : 'System Float' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $txn->created_at->format('d M Y, h:i A') }} • {{ $txn->txn_id }}
                                    </p>
                                </div>
                                
                                @if($txn->sender_id === $user->id)
                                    <span class="text-red-600 font-bold">- ৳{{ number_format($txn->amount, 2) }}</span>
                                @else
                                    <span class="text-green-600 font-bold">+ ৳{{ number_format($txn->amount, 2) }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>