<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Order;
use App\Models\Roll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class RollController extends Controller
{
    public function index()
    {
        return view('rolls.index', [
            'title' => 'Рулоны',
            'materials' => Material::all(),
            'rolls' => Roll::query()
                ->when(request('status'), function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->when(request('search'), function ($query, $search) {
                    return $query->where('roll_code', 'like', '%'.$search.'%');
                })
                ->when(request('material'), function ($query, $material) {
                    return $query->where('material_id', $material);
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
            'canDelete' => $roll->status == 'in_storage',
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

    public function destroy(Roll $roll)
    {
        if ($roll->status != 'in_storage') {
            return redirect()
                ->route('rolls.index')
                ->with('error', 'Рулон уже в работе, не может быть удален');
        }

        $movementMaterial = $roll->movementMaterial;
        $order = $movementMaterial->order;

        $movementMaterial->delete();

        if ($order->movementMaterials->count() == 0) {
            $order->delete();
        }

        $rollCode = $roll->roll_code;

        $roll->delete();

        Log::channel('erp')
            ->notice('Рулон "'.$rollCode.'" удален сотрудником '.auth()->user()->name);

        return redirect()
            ->route('rolls.index')
            ->with('success', 'Рулон удален');
    }
}
