<?php
namespace App\Services;
use App\Models\Item; use App\Models\StockTransaction;
class InventoryService { public function balanceForItem(int $itemId): float { $in=(float)StockTransaction::query()->where('item_id',$itemId)->whereIn('txn_type',['OPENING','IN','ADJUSTMENT','GRN'])->sum('quantity'); $out=(float)StockTransaction::query()->where('item_id',$itemId)->whereIn('txn_type',['OUT','KITCHEN_ISSUE'])->sum('quantity'); return round($in-$out,3);} public function stockBalances(): array { return Item::query()->get()->map(fn($i)=>['item'=>$i,'balance'=>$this->balanceForItem($i->id)])->all(); }}
