<?php

namespace App\Livewire\Operator;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Penimbangan;
use Illuminate\Support\Facades\Auth;

class HistoryTable extends Component
{
    use WithPagination;

    public $lastNotifiedPenimbanganId = null;

    public function mount()
    {
        // Sanitize page parameter to prevent "string + int" error on PHP 8.4
        $page = request()->query('page');
        if (request()->has('page') && (!is_numeric($page) || $page < 1)) {
            $this->setPage(1);
        }

        // Initialize the latest completed/invalid record to avoid notifying on first load
        $latestRecord = Penimbangan::where('user_id', Auth::id())
            ->whereIn('status', ['selesai', 'invalid'])
            ->orderByDesc('updated_at')
            ->first();
            
        if ($latestRecord) {
            $this->lastNotifiedPenimbanganId = $latestRecord->id;
        }
    }

    public function checkUpdate()
    {
        $latestRecord = Penimbangan::where('user_id', Auth::id())
            ->whereIn('status', ['selesai', 'invalid'])
            ->orderByDesc('updated_at')
            ->first();

        if ($latestRecord && $this->lastNotifiedPenimbanganId !== $latestRecord->id) {
            if ($this->lastNotifiedPenimbanganId !== null) {
                $isSuccess = $latestRecord->status === 'selesai';
                $this->dispatch('penimbangan-notified', 
                    status: $latestRecord->status,
                    message: $isSuccess ? "Penimbangan berhasil: " . number_format($latestRecord->berat, 3) . " kg" : "Penimbangan gagal atau invalid.",
                    title: $isSuccess ? 'Berhasil' : 'Gagal'
                );
            }
            $this->lastNotifiedPenimbanganId = $latestRecord->id;
        }
    }

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
