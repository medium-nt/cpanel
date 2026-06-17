@if(($supply->status === 13 && !empty($hasNewOrders) && auth()->user()->isAdmin()) || ($supply->status === 13 && !empty($hasNotReadyOrders) && auth()->user()->isAdmin()) || ($supply->status === 13 && !empty($hasOnSupplyOrders) && auth()->user()->isAdmin()))
    <div class="mb-3">
        @if($supply->status === 13 && !empty($hasNewOrders) && auth()->user()->isAdmin())
            <form
                action="{{ route('marketplace_orders.destroy_new_by_supply', $supply) }}"
                method="POST"
                class="d-inline delete-all-new-form">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash mr-1"></i>
                    Удалить все новые
                </button>
            </form>
        @endif
        @if($supply->status === 13 && !empty($hasNotReadyOrders) && auth()->user()->isAdmin())
            <form
                action="{{ route('marketplace_orders.detach_not_ready_by_supply', $supply) }}"
                method="POST"
                class="d-inline ml-2">
                @csrf @method('DELETE')
                <button type="submit"
                        class="btn btn-warning btn-sm"
                        onclick="return confirm('Убрать из поставки все не готовые заказы (без короба)? Они останутся в системе, но будут отвязаны от поставки.')">
                    <i class="fas fa-unlink mr-1"></i>
                    Убрать "в работе"
                </button>
            </form>
        @endif
        @if($supply->status === 13 && !empty($hasOnSupplyOrders) && auth()->user()->isAdmin())
            <form
                action="{{ route('marketplace_orders.detach_on_supply_by_supply', $supply) }}"
                method="POST"
                class="d-inline ml-2">
                @csrf @method('DELETE')
                <button type="submit"
                        class="btn btn-info btn-sm"
                        onclick="return confirm('Убрать из поставки все заказы в статусе «на поставку» (без короба)? Они останутся в системе, но будут отвязаны от поставки.')">
                    <i class="fas fa-unlink mr-1"></i>
                    Убрать "на поставку"
                </button>
            </form>
        @endif
    </div>
@endif
