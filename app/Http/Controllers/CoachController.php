<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CoachController extends Controller
{

    /**
     * @param Coach $coach
     * @return Application|ResponseFactory|\Illuminate\Foundation\Application|Response
     */
    public function getCoach(Coach $coach)
    {
        $coaches = $coach::query()
            ->get();

        $coachData = [];
        if (!empty($coaches)) {
            $coachesSet = $rowsSet = [];
            $coachName = $rowName = "";
            foreach ($coaches as $coach) {
                $coachData["coach" . $coach->coach_number]["row" . $coach->row_number][$coach->seat_number]['is_reserved'] = $coach->is_reserved;
            }
            $preparedData = [];
            foreach ($coachData as $key => $data) {
                $coachRows = [];
                foreach ($data as $rowName => $row) {
                    $seatData = [];
                    foreach ($row as $seatName => $seats) {
                        $seatData[] = ['seat_name' => $seatName, 'is_reserved' => $seats['is_reserved']];
                    }
                    $coachRows[] = ['row_name' => $rowName, 'seats' => $seatData];
                }
                $preparedData[] = ['coach_name' => $key, 'rows' => $coachRows];
            }
        }
        return response($preparedData);
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|\Illuminate\Foundation\Application|Response
     */
    public function bookSeat(Request $request)
    {
        $seat = $request->seat_count;
        if ($seat > 7 && is_numeric($seat)) {
            return response(['message' => 'Please enter less than 7']);
        }
        $rowSeats = Coach::query()
            ->where('is_reserved', '=', false)
            ->groupBy('row_number')
            ->havingRaw("count(id) >= $seat")
            ->selectRaw('`row_number`, group_concat(`id` order BY `id` ASC) as ids')
            ->orderByRaw('count(`id`)')
            ->get();
        $seats = $this->findConsecutiveSeats($rowSeats, $seat);
        if (empty($seats) || count($seats) < $seat) {
            $rowSeats = Coach::query()
                ->where('is_reserved', '=', false)
                ->groupBy('row_number')
                ->havingRaw("count(id) >= 0")
                ->selectRaw('`row_number`, group_concat(`id` order BY `id` ASC) as ids, count(`id`)')
                ->orderByRaw('count(`id`) DESC, `row_number`')
                ->get();
            $seats = $this->findNearestSeats($rowSeats, $seat);
        }

        $message = "";
        if (count($seats) < $seat) {
            $message = "There is no such seats available";
        } else {
            $seats = array_slice((array) $seats, 0, $seat);
            Coach::query()->whereIn('id', $seats)->update(['is_reserved' => 1]);
        }
        return response(['seats' => $seats, 'message' => $message]);
    }

    /**
     * @param $seats
     * @param $required
     * @return array
     */
    public function findConsecutiveSeats($seats, $required): array
    {
        $resetArray = [];
        foreach ($seats as $seat) {
            $rowSeat = explode(',', $seat->ids);
            $resetArray = [];
            for ($i = 0; $i < count($rowSeat); $i++) {
                $resetArray[] = $rowSeat[$i];
                if (count($resetArray) < $required && isset($rowSeat[$i + 1]) && $rowSeat[$i] + 1 != $rowSeat[$i + 1]) {
                    $resetArray = [];
                }
                if (count($resetArray) == $required) {
                    break;
                }
            }
            if (count($resetArray) >= $required) {
                break;
            }
        }
        return $resetArray;
    }

    /**
     * @param $seats
     * @param $required
     * @return array
     */
    public function findNearestSeats($seats, $required): array
    {
        $prepareSeats = [];
        foreach ($seats as $seat) {
            $rowSeats = explode(',', $seat->ids);
            $prepareSeats = array_merge($prepareSeats, $rowSeats);
            if (count($prepareSeats) >= $required) {
                return $prepareSeats;
            }
        }
        return [];
    }

    /**
     * @param Request $request
     * @param Coach $coach
     * @return Application|ResponseFactory|\Illuminate\Foundation\Application|Response
     */
    public function resetSeats(Request $request, Coach $coach)
    {
        $seats = $request->reset_count ?? 10;
        $arr = range(1, 80);
        shuffle($arr);
        $seats = array_chunk($arr, $seats);
        $seats = $seats[0];
        $coach::query()->where('is_reserved', '=', 1)->update(['is_reserved' => 0]);
        $coach::query()->whereIn('id', $seats)->update(['is_reserved' => 1]);
        return response(['message' => 'Seats successfully reset']);
    }
}