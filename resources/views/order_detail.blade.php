<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Order Management</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.5/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        }
    </script>

    <style>
        .float-panel {
            min-width: 200px;
            z-index: 9;
            position: fixed;
            height: 315px;
            width: 20%;
            left: 30px;
            bottom: 30px;
            border-radius: 10px;
            border-color: #CECEFF;
            border-width: 1px;
            border-style: solid;
            background-color: #ECF5FF;
        }
        .float-panel .line {
            margin-left: 10px;
            width: 95%;
            position: relative;
            background-color: gray;
            top: 250px;
            height: 1px;
        }
        .float-panel .aggregation {
            position: absolute;
            width: 100%;
            bottom: 5px;
        }
        .float-panel .drag_head {
            cursor: move;
            width: 100%;
            height: 20px;
            background-color: #ECF5FF;
            position: absolute;
            top: 5px;
        }
        .float-panel .multiple-check {
            width: 100%;
            position: relative;
            top: 20px;
            padding-left: 5px;
            height: 200px;
        }
        .float-panel .selectedAmount {
            z-index: 10;
            width: 100%;
            position: absolute;
            height: 30px;
            top: 180px;
            left: 10px;
            margin-right: 5px;
            padding-top: 3px;
            padding-right: 20px;
            font-size: 1em;
        }
        .float-panel .mul-checkout {
            z-index: 10;
            width: 100%;
            position: absolute;
            height: 30px;
            top: 210px;
            left: 10px;
            margin-right: 5px;
            padding-top: 3px;
            padding-right: 20px;
            font-size: 1em;
        }
        [x-cloak] {
            display: none !important;
        }
    </style>

</head>
<body class="bg-gray-900 text-white">

    <div class="flex h-screen" x-data="{
            showModal: false,
            id: {{ $order->id }},
            secret: '',
            submit() {
                submitSecret(this);
            },
            authErrorMessage: '',
        }">
        <!-- Popup window overlay -->
        <div x-cloak x-transition x-show="showModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <!-- Popup window content -->
            <div class="bg-gray-900 rounded-lg p-6 w-96">
                <h2 class="text-xl font-semibold mb-4">Please input password</h2>
                <!-- Password input field -->
                <input type="password" placeholder="Please input password" class="w-full p-2 border rounded mb-4 text-black" x-model="secret">
                <!-- Error message -->
                <span x-show="authErrorMessage" class="text-red-500 text-xs mt-1">
                    <span x-text="authErrorMessage"></span>
                </span>
                <!-- Button Section -->
                <div class="flex justify-end space-x-4">
                    <button @click="showModal = false ; authErrorMessage = '' ; secret = ''" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button @click="submit()" class="px-4 py-2 bg-blue-500 text-white rounded">Submit</button>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <nav id="sidebar" class="bg-gray-800 w-64 p-4 fixed h-full transition-transform transform -translate-x-full md:translate-x-0">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-bold mb-4">
                    Order Management
                    <!-- Button to trigger popup window -->
                    <button @click="showModal = true"
                            class="ml-1 px-2 py-1 bg-red-500 text-white rounded text-xs"
                            x-show="{{ $authenticated === 'true' ? 'false' : 'true' }}"
                            x-cloak
                    >Unlock</button>
                    <span class="ml-1 px-2 py-1 bg-green-500 text-white rounded text-xs"
                          x-show="{{ $authenticated }}"
                          x-cloak
                    >Unlocked</span>
                </h3>
                <button onclick="toggleSidebar()" class="text-white focus:outline-none md:hidden">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <ul>
                <li class="mb-2">
                    <p class="block p-2 bg-gray-700 rounded">Orders</p>
                    <ul class="ml-4">
                        @isset($orders)
                            @foreach($orders ?? [] as $o)
                                <li>
                                    <a href="{{ route('order.detail', ['id' => $o->id]) }}" class="block p-1 text-gray-400 hover:text-white">
                                        {{ $o->order_name ?? '訂單' }} | {{ date("m-d", strtotime($o->date)) }} | #{{ $o->id }}
                                    </a>
                                </li>
                            @endforeach
                        @else
                            <li class="text-gray-400 px-4">No available order data.</li>
                        @endisset
                    </ul>
                </li>
                <li><a href="{{ route('order.list') }}" class="block p-2 bg-gray-700 rounded">Back to Home</a></li>
            </ul>
        </nav>

        <!-- Content -->
        <div class="flex-1 p-4 ml-0 md:ml-64">
            <button class="mb-4 p-2 bg-blue-600 rounded md:hidden" onclick="toggleSidebar()">Toogle Menu</button>

            <!-- Order Table -->
            <div class="bg-gray-800 p-4 rounded-lg">
                <h2 class="text-xl font-bold mb-4">
                    Order List
                    <span class="text-red-500 text-xs"
                          x-show="$store.global.errorMessage"
                          x-text="' (' + new Date().toLocaleTimeString() + ') ' + 'Error: ' + $store.global.errorMessage"></span>
                </h2>
                <table class="w-full text-left text-gray-300">
                    <thead>
                    <tr class="border-b border-gray-600">
                        <th>Batch</th>
                        <th>Payment Button</th>
                        <th>Name</th>
                        <th>Subtotal</th>
                        <th>Items</th>
                        <th>Payment Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @isset($order_details)
                        @foreach($order_details as $order_detail)
                            <tr class="border-b border-gray-700"
                                x-data="{
                                    on: {{ $order_detail->pay_status ? 'true' : 'false' }},
                                    id: {{ $order_detail->id }},
                                    name: '{{ $order_detail->name }}',
                                    price: {{ $order_detail->total_price }},
                                    toggleCheckbox() {
                                        handlePayment(this);
                                    },
                                    selected: false,
                                    checkbox() {
                                        handleMultipleSelected(this);
                                    },
                                }"
                                x-init="on ? $store.global.increaseAmount(price) : null"
                            >
                                <!-- Batch -->
                                <td class="px-2 py-2 text-center">
                                    <label class="inline-flex items-center justify-center space-x-2">
                                        <input type="checkbox"
                                               class="form-checkbox h-5 w-5 text-blue-600 border-gray-300 rounded-sm
                                               focus:ring-2 focus:ring-blue-500"
                                               x-model="selected"
                                               @change="checkbox()"
                                               :disabled="on ? true : false"
                                        >
                                    </label>
                                </td>
                                <!-- Payment Button -->
                                <td>
                                    <div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only"
                                                   x-model="on"
                                                   @change="toggleCheckbox()"
                                            >
                                            <div class="w-11 h-6 rounded-full transition-colors"
                                                 :class="on ? 'bg-blue-600' : 'bg-gray-300'"
                                            >
                                                <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform"
                                                     :class="on ? 'translate-x-5' : 'translate-x-0'"></div>
                                            </div>
                                            <span class="ml-2 text-black-700"
                                                  :class="on ? 'text-green-600' : 'text-gray-400'"
                                                  x-text="on ? 'On' : 'Off'"></span>
                                        </label>
                                    </div>
                                </td>
                                <td>{{ $order_detail->name }}</td>
                                <td>${{ $order_detail->total_price }}</td>
                                <td>{{ $order_detail->items }}</td>
                                <td>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-md"
                                          :class="on ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
                                          x-text="on ? 'Paid' : 'Unpaid'">
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @endisset
                    </tbody>
                </table>

                <div x-data class="float-panel" id="panel1">
                    <div class="drag_head text-center text-blue-500 font-bold">
                        <div class="multiple-check text-left" x-text="$store.global.selectedUser"></div>
                    </div>
                    <div class="selectedAmount">
                        <span class="text-blue-500 font-bold float-right" x-text="'※Total selected: ' + $store.global.selectedAmount"></span>
                    </div>
                    <div class="mul-checkout">
                        <button class="h-full text-xs px-2 py-1 bg-teal-500 text-white font-semibold rounded-lg shadow-md hover:bg-teal-400 active:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-opacity-75 " type="button" @click="submitSelected()">Confirm</button>
                        <button class="h-full text-xs px-2 py-1 bg-red-500 text-white font-semibold rounded-lg shadow-md hover:bg-red-400 active:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-opacity-75" type="button" @click="deselectAll()">Cancel Selected</button>
                    </div>
                    <div class="line"></div>
                    <div class="aggregation">
                        <div class="flex flex-col px-1 py-2 text-xs">
                            <!-- Paid Section -->
                            <div class="text-green-500 font-semibold mb-2">
                                Paid: <span x-text="'$' + $store.global.totalAmountPaid"></span>
                            </div>

                            <!-- Total Section -->
                            <div class="text-blue-600 font-semibold">
                                Total {{ $order->items_amount }} items，Order total: ${{ $order->total_price }}
                            </div>
                        </div>


                    </div>
                </div>

            </div>
        </div>
    </div>
    <script>
        // Alpine Global Variables
        document.addEventListener('alpine:init', () => {
            Alpine.store('global', {
                // Paid amount
                totalAmountPaid: 0,
                increaseAmount(price) {
                    this.totalAmountPaid += price;
                },
                reduceAmount(price) {
                    this.totalAmountPaid -= price;
                },
                // Multiple selection handling
                selectedRecords: {}, // Stores selected data in x-data
                selectedUser: '', // Names of selected users, seprated by commas
                selectedAmount: 0, // Total amount for selected users
                addRecord(id, object) {
                    this.selectedRecords[id] = object;
                },
                removeRecord(id) {
                    delete this.selectedRecords[id];
                },
                errorMessage: '',
            });
        });

        // Handle payment funcitonality (Toggle and MultipleCheckbox)
        function handlePayment(alpineObject, isBatch) {
            if (isBatch) {
                alpineObject.on = true;
            }
            axios.post('/order/payment/update', {
                orderDetailId: alpineObject.id,
                status: alpineObject.on
            }, {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            })
                .then(response => {
                    if (!response.data.success) {
                        showErrorMessage('Request successful, but server failed to process.');
                        alpineObject.on = !alpineObject.on;
                    } else {
                        if (alpineObject.on) {
                            Alpine.store('global').increaseAmount(alpineObject.price);
                            // If multiple options are selected, deselect them
                            if (alpineObject.selected) {
                                alpineObject.selected = false;
                                Alpine.store('global').removeRecord(alpineObject.id);
                                refreshSelectedAggregation();
                            }
                        } else {
                            Alpine.store('global').reduceAmount(alpineObject.price);
                        }
                    }
                })
                .catch(error => {
                    showErrorMessage('Request failed');
                    alpineObject.on = !alpineObject.on;
                });
        }

        // Handle multiple selection checkbox click
        function handleMultipleSelected(alpineObject) {
            if (alpineObject.selected) {
                Alpine.store('global').addRecord(alpineObject.id, alpineObject);
            } else {
                Alpine.store('global').removeRecord(alpineObject.id);
            }
            refreshSelectedAggregation();
        }
        // Refresh selectedAmount 和 selectedUser
        function refreshSelectedAggregation() {
            Alpine.store('global').selectedAmount =
                Object.values(Alpine.store('global').selectedRecords).reduce((acc, value) => acc + value.price, 0);
            Alpine.store('global').selectedUser =
                Object.values(Alpine.store('global').selectedRecords).map(value => value.name).join('、');
        }
        // Deselect all
        function deselectAll() {
            // deselect record selected
            Object.values(Alpine.store('global').selectedRecords).forEach(value => value.selected = false);
            Alpine.store('global').selectedRecords = {};
            Alpine.store('global').selectedUser = '';
            Alpine.store('global').selectedAmount = 0;
        }
        // Handle multiple selection submit button
        function submitSelected() {
            Object.values(Alpine.store('global').selectedRecords).forEach(value => {
                handlePayment(value, true);
            });
        }
        // Enter password validation
        function submitSecret(alpineObject) {
            axios.post(`/order/auth`, {
                orderId: alpineObject.id,
                secret: alpineObject.secret,
            }, {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            })
                .then(response => {
                    if (!response.data.success) {
                        alpineObject.authErrorMessage = response.data.message;
                    } else {
                        alpineObject.showModal = false;
                    }
                })
                .catch(error => {
                    alpineObject.authErrorMessage = error.response.data.message;
                });
        }
        let errorMessageTimerId;
        function showErrorMessage(errorMessage) {
            Alpine.store('global').errorMessage = '';
            Alpine.store('global').errorMessage = errorMessage;
            if (errorMessageTimerId) {
                clearTimeout(errorMessageTimerId);
            }
            errorMessageTimerId = setTimeout(() => Alpine.store('global').errorMessage = '', 5000);
        }
    </script>
    <!-- Handle draggle panel -->
    <script>
        // Initialize function
        function init() {
            dragElement(document.getElementById("panel1"));
            addTouchEvents();
        }

        // Make element draggle
        function dragElement(elmnt) {
            let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

            // If there's a header, set it as the drag start point
            const header = document.getElementById(elmnt.id + "header");
            if (header) {
                header.onmousedown = dragMouseDown;
            } else {
                elmnt.onmousedown = dragMouseDown;
            }

            function dragMouseDown(e) {
                e = e || window.event;
                // e.preventDefault();

                // Record the initial mouse position
                pos3 = e.clientX;
                pos4 = e.clientY;

                // Bind mousemove and mouseup events
                document.onmouseup = closeDragElement;
                document.onmousemove = elementDrag;
            }

            // Update element position when mouse moves
            function elementDrag(e) {
                e = e || window.event;
                // e.preventDefault();

                pos1 = pos3 - e.clientX;
                pos2 = pos4 - e.clientY;
                pos3 = e.clientX;
                pos4 = e.clientY;

                // Calculate and update the new position of the element
                elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
                elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
            }

            // Stop dragging
            function closeDragElement() {
                document.onmouseup = null;
                document.onmousemove = null;
            }
        }

        // Touch event Handler
        function touchHandler(event) {
            const touch = event.changedTouches[0];

            const simulatedEvent = document.createEvent("MouseEvent");
            simulatedEvent.initMouseEvent({
                    touchstart: "mousedown",
                    touchmove: "mousemove",
                    touchend: "mouseup"
                }[event.type], true, true, window, 1,
                touch.screenX, touch.screenY,
                touch.clientX, touch.clientY, false,
                false, false, false, 0, null);

            touch.target.dispatchEvent(simulatedEvent);
            // event.preventDefault();
        }

        // Add event listeners for touch events
        function addTouchEvents() {
            document.addEventListener("touchstart", touchHandler, true);
            document.addEventListener("touchmove", touchHandler, true);
            document.addEventListener("touchend", touchHandler, true);
            document.addEventListener("touchcancel", touchHandler, true);
        }

        // Call initialize function
        init();
    </script>

</body>
</html>
