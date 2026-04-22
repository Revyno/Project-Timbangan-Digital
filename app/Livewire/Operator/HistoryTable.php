<?php

namespace App\Livewire\Operator;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Penimbangan;
use Illuminate\Support\Facades\Auth;

class HistoryTable extends Component
{
    use WithPagination;

    public function render()
    {
        $penimbangans = Penimbangan::with(['produk'])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(5);

        return view('livewire.operator.history-table', [
            'penimbangans' => $penimbangans,
        ]);
    }
}
