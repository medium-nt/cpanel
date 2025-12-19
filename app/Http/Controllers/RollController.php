<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Roll;
use Barryvdh\DomPDF\Facade\Pdf;

class RollController extends Controller
{
    public function index()
    {
        return view('rolls.index', [
            'title' => 'Рулоны',
            'rolls' => Roll::query()
                ->when(request('status'), function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->when(request('search'), function ($query, $search) {
                    return $query->where('roll_code', 'like', '%'.$search.'%');
                })
                ->orderBy('id', 'desc')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function show(Roll $roll)
    {
        return view('rolls.show', [
            'title' => 'Рулон '.$roll->roll_code,
            'roll' => Roll::find($roll->id),
        ]);
    }

    public function printRoll(Roll $roll)
    {
        $pdf = PDF::loadView('pdf.roll_sticker', [
            'roll' => $roll,
        ]);

        $roll->update([
            'is_printed' => 1,
        ]);

        return $pdf->setPaper('A4')
            ->download('roll_sticker.pdf');
    }

    public function printOrder(Order $order)
    {
        $rolls = $order->movementMaterials->map->roll;

        if ($rolls->filter()->isEmpty()) {
            echo 'Для этой поставки еще не было добавлено рулонов в систему.';
            exit;
        }

        $pdf = PDF::loadView('pdf.all_rolls_sticker', [
            'rolls' => $rolls,
        ]);

        $rollIds = $order->movementMaterials->pluck('roll_id');
        Roll::whereIn('id', $rollIds)->update([
            'is_printed' => 1,
        ]);

        return $pdf->setPaper('A4')
            ->download('roll_sticker.pdf');
    }
}
