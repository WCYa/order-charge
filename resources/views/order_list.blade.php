<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Order Charge</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.5/dist/cdn.min.js" defer></script>

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-gray-900 text-white">

    <div x-data="{ open: false }" class="flex">
        <!-- Sidebar -->
        <nav class="w-64 min-h-screen bg-gray-800 text-white p-5 transition-all duration-300 md:block"
             :class="open ? 'block' : 'hidden'">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">Whois : {{ request()->ip() }}</h3>
                <button @click="open = false" class="text-white focus:outline-none md:hidden">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <ul class="mt-5 space-y-3">
                <p class="text-sm font-light">Order Charge</p>
                <li class="bg-gray-700 rounded-lg">
                    <p class="block px-4 py-2">Orders</p>
                    <ul class="ml-4 mt-2 space-y-1">
                        @isset($orders)
                            @forelse ($orders as $order)
                                <li>
                                    <a href="{{ route('order.detail', ['id' => $order->id]) }}" class="block px-4 py-1 hover:bg-gray-600 rounded">
                                        {{ $order->order_name ?? 'Orders' }} | {{ date("m-d", strtotime($order->date)) }} | #{{ $order->id }}
                                    </a>
                                </li>
                            @empty
                                <li class="text-gray-400 px-4">No available order data.</li>
                            @endforelse
                        @else
                            <li class="text-gray-400 px-4">No available order data.</li>
                        @endisset
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Sidebar Toggle Button -->
        <button @click="open = !open" class="absolute top-5 left-5 text-white bg-gray-700 px-3 py-2 rounded-lg md:hidden" :class="open ? 'hidden' : 'block'">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Page Content -->
        <div class="flex-1 p-10">
            <h1 class="text-2xl font-bold">
                Paste order information
            </h1>
            <p class="text-gray-300 text-sm mt-2">
                You can paste multiple lines of order information. Example:
            </p>
            <p class="text-gray-300 text-sm mt-2">
                $50 Apple *5 (user1 *1、user2 *1、user3 *2、user4 *1)
            </p>

            <form method="POST" action="{{ route('order.add') }}" class="mt-5">
                @csrf
                <div class="mb-4">
                    <textarea name="order_data" class="w-full p-3 border border-gray-600 bg-gray-700 text-white rounded-lg" rows="10" placeholder="Paste order information..."></textarea>
                    <input name="order_data_format" value="text" type="hidden">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-lg font-semibold">Order Name (Required)</label>
                        <input name="order_name" type="text" class="w-full p-2 border border-gray-600 bg-gray-700 text-white rounded-lg" placeholder="Input order name" required>
                    </div>
                    <div>
                        <label class="block text-lg font-semibold text-red-500">*Order Secret (Required)</label>
                        <input name="order_secret" type="password" class="w-full p-2 border border-gray-600 bg-gray-700 text-white rounded-lg" placeholder="Enter secret" required>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Submit Order
                    </button>
                    <a href="{{ route('order.list') }}" class="ml-4 px-6 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-600 hover:text-white">
                        Refill Form
                    </a>
                </div>
            </form>
        </div>
    </div>
    @if(session('alert'))
        <script>alert("{{ session('alert') }}");</script>
    @endif
    </body>
</html>
