<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Debt;
use App\Models\Entities\Dream;
use App\Models\Entities\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $now   = Carbon::now();
        $items = [];
        $id    = 1;

        // Debts due within 7 days
        $debts = Debt::where('user_id', $user->id)
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '>=', $now->toDateString())
            ->whereDate('next_due_date', '<=', $now->copy()->addDays(7)->toDateString())
            ->where('status', '!=', 'paid')
            ->orderBy('next_due_date')
            ->take(3)
            ->get();

        foreach ($debts as $debt) {
            $daysLeft = (int) $now->diffInDays($debt->next_due_date, false);
            $label    = match (true) {
                $daysLeft === 0 => 'hoy',
                $daysLeft === 1 => 'mañana',
                default         => "en {$daysLeft} días",
            };
            $items[] = [
                'id'     => $id++,
                'icon'   => 'credit_card',
                'tone'   => 'expense',
                'title'  => "{$debt->name} por vencer",
                'body'   => '$' . number_format((float) $debt->next_due_amount, 2) . " vence {$label}.",
                'time'   => $this->timeAgo($debt->next_due_date),
                'unread' => true,
            ];
        }

        // Dreams at milestone (≥50%) — not yet completed
        $dreams = Dream::where('user_id', $user->id)
            ->where('is_completed', false)
            ->where('target_amount', '>', 0)
            ->orderByDesc('updated_at')
            ->take(3)
            ->get();

        foreach ($dreams as $dream) {
            $pct = (int) round(($dream->saved_amount / $dream->target_amount) * 100);
            if ($pct >= 50) {
                $items[] = [
                    'id'     => $id++,
                    'icon'   => 'auto_awesome',
                    'tone'   => 'income',
                    'title'  => "¡{$dream->name} al {$pct}%!",
                    'body'   => 'Vas por buen camino para cumplir tu objetivo.',
                    'time'   => $this->timeAgo($dream->updated_at),
                    'unread' => false,
                ];
            }
        }

        // Recent income transactions (last 5 days, positive amount)
        $income = Transaction::where('user_id', $user->id)
            ->where('amount', '>', 0)
            ->whereDate('date', '>=', $now->copy()->subDays(5)->toDateString())
            ->orderByDesc('date')
            ->take(2)
            ->get();

        foreach ($income as $tx) {
            $items[] = [
                'id'     => $id++,
                'icon'   => 'arrow_downward',
                'tone'   => 'income',
                'title'  => 'Pago recibido',
                'body'   => ($tx->description ?? 'Transacción') . ' · +$' . number_format((float) $tx->amount, 2),
                'time'   => $this->timeAgo($tx->date),
                'unread' => false,
            ];
        }

        if (empty($items)) {
            $items[] = [
                'id'     => 1,
                'icon'   => 'check_circle',
                'tone'   => 'info',
                'title'  => 'Todo al día',
                'body'   => 'No tienes notificaciones pendientes.',
                'time'   => 'Ahora',
                'unread' => false,
            ];
        }

        return response()->json([
            'status' => 'OK',
            'data'   => $items,
            'meta'   => ['count' => count($items), 'unread' => collect($items)->where('unread', true)->count()],
        ]);
    }

    private function timeAgo(mixed $datetime): string
    {
        if (! $datetime) return '';
        $diff = Carbon::now()->diff(Carbon::parse($datetime));
        if ($diff->days === 0 && $diff->h === 0) return 'Hace ' . max($diff->i, 1) . ' min';
        if ($diff->days === 0) return 'Hace ' . $diff->h . ' h';
        if ($diff->days === 1) return 'Ayer';
        if ($diff->days < 7)  return 'Hace ' . $diff->days . ' d';
        return 'Hace ' . (int) ceil($diff->days / 7) . ' sem';
    }
}
