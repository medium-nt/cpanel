<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    public function changeDate(Request $request)
    {
        if ($request->ajax()) {
            Validator::make($request->all(), [
                'user_id' => ['required', 'exists:users,id'],
                'date' => ['required', 'date'],
            ])->validate();

            try {
                $deleted = false;

                $scheduleDate = Schedule::query()->firstOrCreate([
                    'user_id' => $request->user_id,
                    'date' => $request->date,
                ]);

                if (!$scheduleDate->wasRecentlyCreated) {
                    $scheduleDate->delete();
                    $deleted = true;
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Возникла проблема при сохранении расписания',

                ]);
            }

            return response()->json([
                'message' => 'Расписание успешно обновлено',
                'deleted' => $deleted,
                'id' => $scheduleDate->id,
            ]);
        } else {
            abort(404);
        }
    }
}
