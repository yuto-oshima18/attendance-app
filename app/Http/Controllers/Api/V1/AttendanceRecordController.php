<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧を取得する
     *
     * @param  IndexAttendanceRecordRequest  $request  フィルターする情報
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $query = AttendanceRecord::with(['user', 'breakTimes']);

        $validated = $request->validated();

        $query
            ->when(
                ! empty($validated['user_id']),
                fn ($query) => $query->where('user_id', $validated['user_id'])
            )
            ->when(
                ! empty($validated['date']),
                fn ($query) => $query->where('date', $validated['date'])
            )
            ->when(
                ! empty($validated['month']),
                fn ($query) => $query
                    ->whereYear('date', substr($validated['month'], 0, 4))
                    ->whereMonth('date', substr($validated['month'], 5, 2))
            );

        // 1ページあたりの件数（デフォルト: 20、最大: 100）
        $perPage = 20;
        if (! empty($validated['per_page'])) {
            $perPage = $validated['per_page'];
        }

        $attendanceRecord_records = $query->latest('date')->paginate($perPage);

        /** @var LengthAwarePaginator $attendanceRecord_records */
        $attendanceRecord_records->getCollection()->each(function ($attendanceRecord) {
            // 休憩時間の合計を計算
            $totalBreakSeconds = $attendanceRecord->breakTimes->sum(function ($breakTime) {
                if (! $breakTime->break_in || ! $breakTime->break_out) {
                    return 0;
                }

                return strtotime($breakTime->break_out) - strtotime($breakTime->break_in);
            });

            // 休憩時間の合計を追加
            if (! empty($totalBreakSeconds)) {
                $attendanceRecord->total_break_time = gmdate('H:i', $totalBreakSeconds);
            }

            // 勤務時間
            $totalWorkSeconds = 0;

            if ($attendanceRecord->clock_in && $attendanceRecord->clock_out) {
                $totalWorkSeconds = strtotime($attendanceRecord->clock_out) - strtotime($attendanceRecord->clock_in);
            }

            $totalTimeSeconds = $totalWorkSeconds - $totalBreakSeconds;

            // 勤務時間の合計を追加
            if (! empty($totalTimeSeconds)) {
                $attendanceRecord->total_time = gmdate('H:i', $totalTimeSeconds);
            }
        });

        return AttendanceRecordResource::collection($attendanceRecord_records);
    }

    /**
     * 勤怠を新規登録する
     * 引数にて勤怠情報を受け取り登録する。
     *
     * @param  StoreAttendanceRecordRequest  $request  登録する勤怠情報
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $attendanceRecord = $request->user()->attendanceRecords()->create($validated);
        $attendanceRecord->load(['user', 'breakTimes']);

        return (new AttendanceRecordResource($attendanceRecord))->response()->setStatusCode(201);
    }

    /**
     * 勤怠詳細を取得する
     *
     * @param  AttendanceRecord  $attendanceRecord  ルートモデルバインディングで解決した勤怠情報
     */
    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load(['user', 'breakTimes', 'applications']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠詳細を取得する
     *
     * @param  UpdateAttendanceRecordRequest  $request  更新する情報
     * @param  AttendanceRecord  $attendanceRecord  ルートモデルバインディングで解決した勤怠情報
     */
    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $validated = $request->validated();

        $this->authorize('update', $attendanceRecord);

        $attendanceRecord->update($validated);
        $attendanceRecord->load(['user', 'breakTimes']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠を削除する
     *
     * @param  AttendanceRecord  $attendanceRecord  ルートモデルバインディングで解決した勤怠情報
     */
    public function destroy(AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorize('delete', $attendanceRecord);
        $attendanceRecord->delete();

        return response()->json(null, 204);
    }
}
