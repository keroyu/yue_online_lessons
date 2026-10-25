<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Collection;

/**
 * Builds rows for Simpany's "電子發票批次匯入 - B2C" CSV (009 US8).
 *
 * One invoice per cart order (FR-013); order-less purchases (Portaly, legacy
 * PayUni direct buys) are one invoice each. Amounts are what the buyer paid.
 */
class SimpanyInvoiceExportService
{
    public const HEADERS = [
        '自訂編號', '買受人 Email *', '買受人名稱', '發票備註', '特殊需求 *', '捐贈碼 *',
        '載具類型 *', '載具號碼 *', '課稅別 *', '零稅率原因 *', '含稅發票總金額 *',
        '品名 1 *', '數量 1 *', '含稅單價 1 *',
        '品名 2', '數量 2', '含稅單價 2',
        '品名 3', '數量 3', '含稅單價 3',
        '品名 4', '數量 4', '含稅單價 4',
        '品名 5', '數量 5', '含稅單價 5',
    ];

    private const MAX_ITEMS = 5;

    /**
     * @param  Collection<int, Purchase>  $purchases  with order, course and plan loaded
     * @return array{rows: array<int, array<int, string|int>>, skipped: array{refunded: int, b2b: int, zero: int}}
     */
    public function build(Collection $purchases): array
    {
        $skipped = ['refunded' => 0, 'b2b' => 0, 'zero' => 0];
        $orderPrices = $this->allocateOrderPrices($purchases);

        // Preserve the incoming order while grouping cart purchases by order
        $invoices = [];
        foreach ($purchases as $purchase) {
            $price = $purchase->order_id
                ? ($orderPrices[$purchase->id] ?? 0)
                : (int) round($purchase->amount - $purchase->discount_amount);

            // FR-016: one reason per purchase, checked in this order
            if ($purchase->status === 'refunded') {
                $skipped['refunded']++;
                continue;
            }
            if ($purchase->order?->tax_id) {
                $skipped['b2b']++;
                continue;
            }
            if ($price <= 0 || in_array($purchase->type, ['gift', 'system_assigned'], true)) {
                $skipped['zero']++;
                continue;
            }

            $key = $purchase->order_id ? 'o' . $purchase->order_id : 'p' . $purchase->id;
            $invoices[$key] ??= ['purchase' => $purchase, 'items' => []];
            $invoices[$key]['items'][] = [$this->itemName($purchase), $price];
        }

        $rows = array_map(fn ($invoice) => $this->row($invoice['purchase'], $invoice['items']), array_values($invoices));

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    /**
     * Render header + rows the way Simpany's own template is written: UTF-8
     * without BOM, CRLF, and quotes only where CSV needs them (fputcsv would
     * quote every field containing a space, e.g. "1 - 應稅").
     *
     * @param  array<int, array<int, string|int>>  $rows
     */
    public function toCsv(array $rows): string
    {
        $lines = array_map(
            fn (array $fields) => implode(',', array_map($this->csvField(...), $fields)),
            [self::HEADERS, ...$rows]
        );

        return implode("\r\n", $lines) . "\r\n";
    }

    private function csvField(string|int $value): string
    {
        $value = (string) $value;

        return preg_match('/[",\r\n]/', $value)
            ? '"' . str_replace('"', '""', $value) . '"'
            : $value;
    }

    /**
     * FR-014: split order.total_amount across every purchase of the order by
     * list price, so an item's price doesn't depend on what else was selected.
     *
     * @return array<int, int> purchase id => allocated price
     */
    private function allocateOrderPrices(Collection $purchases): array
    {
        $orders = $purchases->pluck('order')->filter()->keyBy('id');
        if ($orders->isEmpty()) {
            return [];
        }

        $siblings = Purchase::whereIn('order_id', $orders->keys())
            ->orderBy('id')
            ->get(['id', 'order_id', 'amount'])
            ->groupBy('order_id');

        $prices = [];
        foreach ($siblings as $orderId => $items) {
            $total = (int) round($orders[$orderId]->total_amount);
            $listSum = (float) $items->sum('amount');
            $allocated = 0;

            foreach ($items->values() as $i => $item) {
                $price = $i === $items->count() - 1
                    ? $total - $allocated
                    : ($listSum > 0 ? (int) round($total * $item->amount / $listSum) : 0);
                $allocated += $price;
                $prices[$item->id] = $price;
            }
        }

        return $prices;
    }

    private function itemName(Purchase $purchase): string
    {
        $courseName = $purchase->course?->name ?? '';

        return $purchase->plan ? "【{$courseName}】{$purchase->plan->name}" : $courseName;
    }

    /**
     * @param  array<int, array{0: string, 1: int}>  $items
     * @return array<int, string|int>
     */
    private function row(Purchase $purchase, array $items): array
    {
        // FR-015: Simpany takes five items; fold the rest into the fifth
        if (count($items) > self::MAX_ITEMS) {
            $rest = array_slice($items, self::MAX_ITEMS - 1);
            $items = array_slice($items, 0, self::MAX_ITEMS - 1);
            $items[] = ['其他課程（' . count($rest) . ' 堂）', array_sum(array_column($rest, 1))];
        }

        $itemColumns = [];
        foreach (range(0, self::MAX_ITEMS - 1) as $i) {
            $itemColumns = array_merge($itemColumns, isset($items[$i]) ? [$items[$i][0], 1, $items[$i][1]] : ['', '', '']);
        }

        $order = $purchase->order;

        return [
            $order?->merchant_order_no ?: ($purchase->portaly_order_id ?: 'P' . $purchase->id),
            $order?->buyer_email ?: ($purchase->buyer_email ?: ($purchase->user?->email ?? '')),
            '',
            '',
            '1 - 無特殊需求',
            '',
            '',
            '',
            '1 - 應稅',
            '',
            array_sum(array_column($items, 1)),
            ...$itemColumns,
        ];
    }
}
