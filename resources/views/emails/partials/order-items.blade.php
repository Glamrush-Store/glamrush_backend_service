@component('mail::table')
| Item | Qty | Unit | Total |
| :--- | --: | --: | --: |
@foreach ($order->items as $item)
| {{ $item->product_name }}<br><small>{{ $item->sku }}</small> | {{ $item->quantity }} | {{ $order->currency }} {{ number_format((float) $item->unit_price, 2) }} | {{ $order->currency }} {{ number_format((float) $item->line_total, 2) }} |
@endforeach
@endcomponent
