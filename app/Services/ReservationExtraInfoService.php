<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\StudentAcademicInfo;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ReservationExtraInfoService
{
    public function __construct(private readonly ActivityLogService $activityLog)
    {
    }

    public function updateForReservation(Reservation $reservation, array $data, User $user, bool $canUpdatePhone): void
    {
        DB::transaction(function () use ($reservation, $data, $user, $canUpdatePhone): void {
            $reservation = Reservation::query()->with(['student.phones', 'academicInfo'])->lockForUpdate()->findOrFail($reservation->id);
            $student = $reservation->student;
            $old = [
                'student' => Arr::only($student->getAttributes(), ['full_name', 'major', 'region', 'score']),
                'academic_info' => $reservation->academicInfo?->getAttributes(),
            ];

            $student->fill(Arr::only($data, ['full_name', 'major', 'region']));
            $student->score = $data['konkur_score'] ?? null;
            $student->save();

            if ($canUpdatePhone && array_key_exists('phone', $data)) {
                $primaryPhone = $student->phones->firstWhere('is_primary', true) ?? $student->phones->first();
                filled($data['phone'])
                    ? ($primaryPhone ? $primaryPhone->update(['phone' => $data['phone'], 'is_primary' => true]) : $student->phones()->create(['phone' => $data['phone'], 'label' => 'شماره اول', 'is_primary' => true]))
                    : $primaryPhone?->delete();
            }

            $academicData = Arr::only($data, ['rank', 'national_rank', 'total_score', 'final_score', 'accepted_national_field', 'accepted_azad_other_field']);
            $academicInfo = StudentAcademicInfo::query()->updateOrCreate(
                ['reservation_id' => $reservation->id],
                ['student_id' => $student->id, ...$academicData],
            );

            $this->activityLog->log(
                'reservation_extra_info_updated',
                $reservation,
                $user,
                $old,
                ['student' => Arr::only($student->fresh()->getAttributes(), ['full_name', 'major', 'region', 'score']), 'academic_info' => $academicInfo->getAttributes()],
                'اطلاعات تکمیلی دانش‌آموز بروزرسانی شد.',
            );
        });
    }
}
